<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
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
	public static function getName()
	{
		return 'for';
	}

	public static function isElseCompatible()
	{
		return false;
	}

	public static function isSelfClosing()
	{
		return false;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $node)
	{
		$tplEngine->checkRequiredAttributes($node, ['value', 'var']);

		$dataKeyAttr = $node->getAttribute('value')->value;
		$asVarAttr = $node->getAttribute('var')->value;
		$stepAttr = $node->getAttribute('groups');

		$dataKey = $dataKeyAttr;
		$asVar = $asVarAttr;
		$step = ($stepAttr->value === null) ? 1 : intval($stepAttr->value);

		$firstClassAttr = $node->getAttribute('classfirst');
		$firstClass = ($firstClassAttr !== null) ? $firstClassAttr->value : null;

		$lastClassAttr = $node->getAttribute('classlast');
		$lastClass = ($lastClassAttr !== null) ? $lastClassAttr->value : null;
		$forUID = str_replace('.', '', uniqid('', true));

		$this->str_replace_node($node->childNodes);

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

		$node->parentNode->insertBefore($nodeForStart, $node);

		$forPattern = '/(.*?)(\/\* for: start \*\/.*\/\* for: end \*\/ \?>)(.*)/ims';
		$nodeInnerHtml = $node->getInnerHtml();

		if (preg_match($forPattern, $nodeInnerHtml, $resVal)) {
			$nodeInnerHtml = $resVal[1] . $resVal[2] . "<?php \$this->addData('_count', \$i_{$forUID}, true); ?>" . $resVal[3];
		}

		// No fist/last class magic
		if ($firstClass === null && $lastClass === null) {
			$txtForNode = new TextNode($tplEngine->getDomReader());
			$txtForNode->content = $nodeInnerHtml;
			$node->parentNode->insertBefore($txtForNode, $node);

			$node->parentNode->insertBefore($nodeForEnd, $node);
			$node->parentNode->removeNode($node);

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
			$classVal = $classAttr->value;

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
		$node->parentNode->insertBefore($txtForNode, $node);

		$node->parentNode->insertBefore($nodeForEnd, $node);
		$node->parentNode->removeNode($node);
	}

	private function str_replace_node($nodeList)
	{
		$pattern1 = '/\$\{(?:(\d+?)\:)?(\w+?)(?:\.([\w|\.]+?))?\}/';
		$pattern2 = '/\{(?:(\d+?)\:)?(\w+?)(?:\.([\w|\.]+?))?\}/';

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

		return $nodeList;
	}

	public function replaceEcho($m)
	{
		$further = isset($m[3]) ? '->' . str_replace('.', '->', $m[3]) : null;

		return '<?php echo $' . $m[2] . ((is_numeric($m[1]) === true) ? $m[1] : null) . $further . '; ?>';
	}

	public function replaceVar($m)
	{
		return '$' . $m[2] . ((is_numeric($m[1]) === true) ? $m[1] : null) . '->' . str_replace('.', '->', $m[3]);
	}
}
/* EOF */