<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\customtags;

use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use Exception;
use framework\template\template\TemplateTag;

class ElseifTag extends TemplateTag implements TagNode
{
	public static function getName()
	{
		return 'elseif';
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
		$tplEngine->checkRequiredAttributes($tagNode, ['cond']);

		$condAttr = $tagNode->getAttribute('cond')->value;

		$phpCode = '<?php ';

		$phpCode .= 'elseif(' . preg_replace_callback('/\${(.*?)}/i', function($m) {
				if (strlen($m[1]) === 0) {
					throw new Exception('Empty template data reference');
				}

				return '$this->getDataFromSelector(\'' . $m[1] . '\')';
			}, $condAttr) . '): ?>';
		$phpCode .= $tagNode->getInnerHtml();

		if ($tplEngine->isFollowedBy($tagNode, ['else', 'elseif']) === false) {
			$phpCode .= '<?php endif; ?>';
		}

		$textNode = new TextNode($tplEngine->getDomReader());
		$textNode->content = $phpCode;

		$tagNode->parentNode->replaceNode($tagNode, $textNode);
		$tagNode->parentNode->removeNode($tagNode);
	}
}
/* EOF */