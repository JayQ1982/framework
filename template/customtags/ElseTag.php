<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\template\customtags;

use Exception;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\template\TemplateTag;

class ElseTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'else';
	}

	public static function isElseCompatible(): bool
	{
		return false;
	}

	public static function isSelfClosing(): bool
	{
		return false;
	}

	public function replaceNode(TemplateEngine $tplEngine, ElementNode $elementNode): void
	{
		$lastTplTag = $tplEngine->getLastTplTag();

		if ($lastTplTag === null) {
			throw new Exception('There is no custom tag that can be followed by an ElseTag');
		}

		$phpCode = '<?php } else { ?>';
		$phpCode .= $elementNode->getInnerHtml();
		$phpCode .= '<?php } ?>';

		$textNode = new TextNode();
		$textNode->content = $phpCode;

		$elementNode->parentNode->replaceNode($elementNode, $textNode);

		$elementNode->parentNode->removeNode($elementNode);
	}
}