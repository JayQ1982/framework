<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\template\customtags;

use Exception;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\template\TemplateTag;

class ElseifTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'elseif';
	}

	public static function isElseCompatible(): bool
	{
		return true;
	}

	public static function isSelfClosing(): bool
	{
		return false;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $elementNode): void
	{
		$tplEngine->checkRequiredAttributes($elementNode, ['cond']);

		$condAttr = $elementNode->getAttribute('cond')->getValue();

		$phpCode = '<?php ';

		$phpCode .= 'elseif(' . preg_replace_callback(
				pattern: '/\${(.*?)}/i',
				callback: function($m) {
					if (strlen($m[1]) === 0) {
						throw new Exception('Empty template data reference');
					}

					return '$this->getDataFromSelector(\'' . $m[1] . '\')';
				},
				subject: $condAttr
			) . '): ?>';
		$phpCode .= $elementNode->getInnerHtml();

		if ($tplEngine->isFollowedBy($elementNode, ['else', 'elseif']) === false) {
			$phpCode .= '<?php endif; ?>';
		}

		$textNode = new TextNode();
		$textNode->content = $phpCode;

		$elementNode->parentNode->replaceNode($elementNode, $textNode);
		$elementNode->parentNode->removeNode($elementNode);
	}
}