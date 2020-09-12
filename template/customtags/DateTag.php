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

class DateTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'date';
	}

	public static function isElseCompatible()
	{
		return false;
	}

	public static function isSelfClosing()
	{
		return true;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $tagNode)
	{
		$format = $tagNode->getAttribute('format')->value;
		$replNode = new TextNode($tplEngine->getDomReader());
		$replNode->content = '<?php echo date(\'' . $format . '\'); ?>';

		$tagNode->parentNode->replaceNode($tagNode, $replNode);
	}
}
/* EOF */