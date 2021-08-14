<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\customtags;

use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\htmlparser\HtmlNode;
use framework\template\htmlparser\HtmlDoc;
use framework\template\template\TemplateTag;

class ForTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'for';
	}

	public static function isElseCompatible(): bool
	{
		return false;
	}

	public static function isSelfClosing(): bool
	{
		return false;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $elementNode): void
	{
		$tplEngine->checkRequiredAttributes($elementNode, ['value', 'var']);

		$dataKeyAttr = $elementNode->getAttribute('value')->getValue();
		$asVarAttr = $elementNode->getAttribute('var')->getValue();
		$stepAttr = $elementNode->getAttribute('groups');

		$dataKey = $dataKeyAttr;
		$asVar = $asVarAttr;
		$step = ($stepAttr->getValue() === null) ? 1 : intval($stepAttr->getValue());

		$firstClassAttr = $elementNode->getAttribute('classfirst');
		$firstClass = ($firstClassAttr !== null) ? $firstClassAttr->getValue() : null;

		$lastClassAttr = $elementNode->getAttribute('classlast');
		$lastClass = ($lastClassAttr !== null) ? $lastClassAttr->getValue() : null;
		$forUID = str_replace('.', '', uniqid('', true));

		$this->str_replace_node($elementNode->childNodes);

		$nodeForStart = new TextNode($tplEngine->getDomReader());
		$nodeForStart->content = "<?php\n";
		$nodeForStart->content .= "/* for: start */ \$tmpArr = \$this->getDataFromSelector('{$dataKey}');\n";
		$nodeForStart->content .= "if(\$tmpArr === null) \$tmpArr = array();\n";
		$nodeForStart->content .= "\$arr_{$forUID} = array_values((is_object(\$tmpArr) === false)?\$tmpArr:(array)\$tmpArr);\n";
		$nodeForStart->content .= "\$arrCount_{$forUID} = count(\$arr_{$forUID});\n";
		$nodeForStart->content .= "\$i_{$forUID} = 0;\n";

		$nodeForStart->content .= "for(\$i_{$forUID} = 0; \$i_{$forUID} < \$arrCount_{$forUID}; \$i_{$forUID} = \$i_{$forUID}+{$step}) {\n";

		if ($step === 1) {
			$nodeForStart->content .= "\t\$this->addData('{$asVar}', \$arr_{$forUID}[\$i_{$forUID}], true);\n";
			$nodeForStart->content .= "\t\${$asVar} = \$arr_{$forUID}[\$i_{$forUID}];";
		} else {
			for ($i = 0; $i < $step; $i++) {
				$nodeForStart->content .= "\t\$this->addData('" . $asVar . ($i + 1) . "', (isset(\$arr_{$forUID}[\$i_{$forUID}+{$i}]) === true)?\$arr_{$forUID}[\$i_{$forUID}+{$i}]:null, true);\n";
				$nodeForStart->content .= "\t\$" . $asVar . ($i + 1) . " = (isset(\$arr_{$forUID}[\$i_{$forUID}+{$i}]) === true)?\$arr_{$forUID}[\$i_{$forUID}+{$i}]:null;";
			}
		}

		$nodeForStart->content .= "\t\$this->addData('_count', \$i_{$forUID}, true);\n";
		$nodeForStart->content .= "?>";

		$nodeForEnd = new TextNode($tplEngine->getDomReader());
		$nodeForEnd->content = '<?php } $this->unsetData(\'' . $asVar . '\'); $this->unsetData(\'_count\'); /* for: end */ ?>';

		$elementNode->parentNode->insertBefore($nodeForStart, $elementNode);

		$forPattern = '/(.*?)(\/\* for: start \*\/.*\/\* for: end \*\/ \?>)(.*)/ims';
		$nodeInnerHtml = $elementNode->getInnerHtml();

		if (preg_match($forPattern, $nodeInnerHtml, $resVal)) {
			$nodeInnerHtml = $resVal[1] . $resVal[2] . "<?php \$this->addData('_count', \$i_{$forUID}, true); ?>" . $resVal[3];
		}

		// No fist/last class magic
		if ($firstClass === null && $lastClass === null) {
			$txtForNode = new TextNode($tplEngine->getDomReader());
			$txtForNode->content = $nodeInnerHtml;
			$elementNode->parentNode->insertBefore($txtForNode, $elementNode);

			$elementNode->parentNode->insertBefore($nodeForEnd, $elementNode);
			$elementNode->parentNode->removeNode($elementNode);

			return;
		}

		if (preg_match($forPattern, $nodeInnerHtml, $resVal)) {
			$nodeInnerHtml = $resVal[1] . $resVal[2] . " \$_count = \$i_{$forUID}; " . $resVal[3];
		}

		$forDOM = new HtmlDoc($nodeInnerHtml, null);
		$forDOM->parse();

		foreach ($forDOM->getNodeTree()->childNodes as $forNode) {
			if (($forNode instanceof ElementNode) === false) {
				continue;
			}

			/** @var ElementNode $forNode */
			$classAttr = $forNode->getAttribute('class');
			$classVal = $classAttr->getValue();

			if ($classVal === null) {
				$firstClassStr = ($firstClass !== null) ? ' class="' . $firstClass . '"' : null;
				$lastClassStr = ($lastClass !== null) ? ' class="' . $lastClass . '"' : null;
				$firstLastClassStr = ' class="' . (($firstClass !== null && $lastClass !== null) ? $firstClass . ' ' . $lastClass : (($firstClass !== null) ? $firstClass : $lastClass)) . '"';

				$firstLast = "<?php echo ((\$arrCount_{$forUID} === 1)?'{$firstLastClassStr}':(\$i_{$forUID} === 0)?'{$firstClassStr}':((\$arrCount_{$forUID} === \$i_{$forUID}+1)?'{$lastClassStr}':null)); ?>";
			} else {
				$space = ($classVal !== '') ? ' ' : null;

				$firstClassStr = ($firstClass !== null) ? $space . $firstClass : null;
				$lastClassStr = ($lastClass !== null) ? $space . $lastClass : null;
				$firstLastClassStr = (($firstClass !== null && $lastClass !== null) ? $space . $firstClass . ' ' . $lastClass : (($firstClass !== null) ? $space . $firstClass : $space . $lastClass));

				$firstLast = ' class="' . $classVal . '<?php echo (($arrCount_' . $forUID . ' === 1)?\'' . $firstLastClassStr . '\':(($i_' . $forUID . ' === 0)?\'' . $firstClassStr . '\':(($arrCount_' . $forUID . ' === $i_' . $forUID . '+1)?\'' . $lastClassStr . '\':null))); ?>"';
			}

			$forNode->tagExtension = $firstLast;
			$forNode->removeAttribute('class');
		}

		$txtForNode = new TextNode($tplEngine->getDomReader());
		$txtForNode->content = $forDOM->getHtml();
		$elementNode->parentNode->insertBefore($txtForNode, $elementNode);

		$elementNode->parentNode->insertBefore($nodeForEnd, $elementNode);
		$elementNode->parentNode->removeNode($elementNode);
	}

	private function str_replace_node($nodeList): void
	{
		$pattern1 = '/{(?:(\d+?):)?(\w+?)(?:\.([\w|.]+?))?}/';
		$pattern2 = '/{(?:(\d+?):)?(\w+?)(?:\.([\w|.]+?))?}/';

		foreach ($nodeList as $node) {
			$t1 = preg_replace_callback($pattern1, [$this, 'replaceVar'], $node->content);
			$node->content = preg_replace_callback($pattern2, [$this, 'replaceEcho'], $t1);

			if ($node->nodeType !== HtmlNode::ELEMENT_NODE) {
				continue;
			}

			foreach ($node->attributes as $attr) {
				$attr->value = preg_replace_callback($pattern2, [$this, 'replaceEcho'], $attr->value);
			}

			if ($node->tagExtension !== null) {
				$node->tagExtension = preg_replace_callback($pattern1, [$this, 'replaceVar'], $node->tagExtension);
			}

			if (count($node->childNodes) > 0) {
				$this->str_replace_node($node->childNodes);
			}
		}
	}

	public function replaceEcho($m): string
	{
		$further = isset($m[3]) ? '->' . str_replace('.', '->', $m[3]) : null;

		return '<?php echo $' . $m[2] . ((is_numeric($m[1]) === true) ? $m[1] : null) . $further . '; ?>';
	}

	public function replaceVar($m): string
	{
		return '$' . $m[2] . ((is_numeric($m[1]) === true) ? $m[1] : null) . '->' . str_replace('.', '->', $m[3]);
	}
}