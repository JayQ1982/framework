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

class RadioTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'radio';
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
		$selsStr = '$this->getDataFromSelector(\'' . $sels . '\')';
		$value = $node->getAttribute('value')->value;
		$node->removeAttribute('selection');

		$node->namespace = null;
		$node->tagName = 'input';
		if ($sels !== null) {
			$node->tagExtension = " <?php echo ((is_array({$selsStr}) && in_array({$value}, {$selsStr})) || ({$selsStr} == '{$value}'))?' checked':null; ?>";
		}

		$node->addAttribute(new HtmlAttribute('type', 'radio'));
	}
}
/* EOF */