<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\customtags;

use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\HtmlAttribute;
use framework\template\template\TemplateTag;

class OptionTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'option';
	}

	public static function isElseCompatible()
	{
		return false;
	}

	public static function isSelfClosing()
	{
		return true;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $node)
	{
		$sels = $node->getAttribute('selection')->value;
		$valueAttr = $node->getAttribute('value')->value;
		$value = is_numeric($valueAttr) ? $valueAttr : "'" . $valueAttr . "'";
		$type = $node->getAttribute('type')->value;
		$node->removeAttribute('selection');

		$node->namespace = null;
		$node->tagName = 'input';
		if ($sels !== null) {
			$node->tagExtension = " <?php echo in_array({$value}, \$this->getData('{$sels}'))?' checked=\"checked\"':null; ?>";
		}
		$node->addAttribute(new HtmlAttribute('type', $type));
	}
}
/* EOF */