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

class SubNaviTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'subnavi';
	}

	public static function isElseCompatible()
	{
		return true;
	}

	public static function isSelfClosing()
	{
		return false;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $tagNode)
	{
		$sites = array_map('trim', explode(',', $tagNode->getAttribute('sites')->value));

		$phpCode = '<?php if(' . __CLASS__ . '::getCurrentNavi(array(\'' . implode('\',\'', $sites) . '\'), $this)) { ?>';
		$phpCode .= $tagNode->getInnerHtml();

		if ($tplEngine->isFollowedBy($tagNode, ['else']) === false) {
			$phpCode .= '<?php } ?>';
		}

		$textNode = new TextNode($tplEngine->getDomReader());
		$textNode->content = $phpCode;

		$tagNode->parentNode->replaceNode($tagNode, $textNode);
		$tagNode->parentNode->removeNode($tagNode);
	}

	public static function getCurrentNavi($navigationLevelsTpl, TemplateEngine $tplEngine)
	{
		$navigationLevels = $tplEngine->getData('_activeHtmlIds');
		if (!is_array($navigationLevels)) {
			return false;
		}
		foreach ($navigationLevels as $level) {
			if (in_array($level, $navigationLevelsTpl)) {
				return true;
			}
		}

		return false;
	}
}
/* EOF */