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
use Exception;

class AuthTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'AuthTag';
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
		$tplEngine->checkRequiredAttributes($tagNode, ['loggedIn', 'accessRights']);

		$loggedIn = $tagNode->getAttribute('loggedIn')->value;
		$accessRights = trim((string)$tagNode->getAttribute('accessRights')->value);
		if ($accessRights == '') {
			$arr = 'array()';
		} else {
			$accessRights = explode(',', $accessRights);
			$arr = 'array(\'' . implode('\', \'', $accessRights) . '\')';
		}

		$phpCode = '<?php if(' . __CLASS__ . '::checkAccess(' . $loggedIn . ', ' . $arr . ', $this) === true) { ?>';
		$phpCode .= $tagNode->getInnerHtml();

		if ($tplEngine->isFollowedBy($tagNode, ['else']) === false) {
			$phpCode .= '<?php } ?>';
		}

		$textNode = new TextNode($tplEngine->getDomReader());
		$textNode->content = $phpCode;

		$tagNode->parentNode->replaceNode($tagNode, $textNode);
	}

	public static function checkAccess($loggedIn, $accessRights, TemplateEngine $tplEngine)
	{
		$auth = $tplEngine->getData('_auth');

		if ($auth === null) {
			throw new Exception('No auth object accessible');
		}

		return $auth->checkAccess($loggedIn, $accessRights);
	}
}
/* EOF */