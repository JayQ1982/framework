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
use framework\template\template\TemplateTag;

class ForgroupTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'forgroup';
	}

	public static function isElseCompatible()
	{
		return false;
	}

	public static function isSelfClosing()
	{
		return false;
	}

	private string $var;
	private string $no;

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $node)
	{
		$var = $node->getAttribute('var')->value;

		$entryNoArr = explode(':', $var);
		$this->no = $entryNoArr[0];
		$this->var = $entryNoArr[1];

		$tplEngine->checkRequiredAttributes($node, ['var']);

		$replNode = new TextNode($tplEngine->getDomReader());

		$varName = $this->var . $this->no;

		$replNode->content = "<?php \$tmpGrpVal = \$this->getDataFromSelector('{$varName}', true);\n";
		$replNode->content .= " if(\$tmpGrpVal !== null) {\n";
		$replNode->content .= "\$this->addData('{$this->var}', \$tmpGrpVal, true); ?>";
		$replNode->content .= self::prepareHtml($node->getInnerHtml());
		$replNode->content .= "<?php } ?>";

		$node->getParentNode()->replaceNode($node, $replNode);
	}

	private function prepareHtml($html)
	{
		$newHtml = preg_replace_callback('/{' . $this->var . '\.(.*?)}/', [$this, 'replace'], $html);

		return preg_replace_callback('/{(\w+?)(?:\.([\w|.]+))?}/', [$this, 'replaceForeign'], $newHtml);
	}

	private function replaceForeign($matches)
	{
		return '<?php echo $' . $matches[1] . '->' . str_replace('.', '->', $matches[2]) . '; ?>';
	}

	private function replace($matches)
	{
		return '<?php echo $' . $this->var . $this->no . '->' . str_replace('.', '->', $matches[1]) . '; ?>';
	}
}
/* EOF */