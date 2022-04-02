<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\template\customtags;

use framework\html\HtmlTagAttribute;
use framework\template\htmlparser\ElementNode;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\template\TemplateTag;

class OptionTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'option';
	}

	public static function isElseCompatible(): bool
	{
		return false;
	}

	public static function isSelfClosing(): bool
	{
		return true;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $elementNode): void
	{
		$sels = $elementNode->getAttribute('selection')->getValue();
		$valueAttr = $elementNode->getAttribute('value')->getValue();
		$value = is_numeric($valueAttr) ? $valueAttr : "'" . $valueAttr . "'";
		$type = $elementNode->getAttribute('type')->getValue();
		$elementNode->removeAttribute('selection');

		$elementNode->namespace = null;
		$elementNode->tagName = 'input';
		if ($sels !== null) {
			$elementNode->tagExtension = ' <?php echo in_array('.$value.', $this->getData(\''.$sels.'\'))?\' checked="checked"\':null; ?>';
		}
		$elementNode->addAttribute(new HtmlTagAttribute('type', $type, true));
	}
}