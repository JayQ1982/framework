<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\customtags;

use framework\datacheck\Sanitizer;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TemplateTag;

class SubSiteTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'subsite';
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
		$trimmedSitesString = '';
		$inputValue = Sanitizer::trimmedString(input: $elementNode->getAttribute('sites')->getValue());
		if($inputValue !== '') {
			$sites = [];
			foreach(explode(',', $inputValue) as $item) {
				$sites[] = '\''.Sanitizer::trimmedString($item).'\'';
			}
			$trimmedSitesString = implode(',', $sites);
		}

		$phpCode = '<?php if(in_array(SubSiteTag::getData(\'_fileTitle\'),[' . $trimmedSitesString . '])) { ?>';
		$phpCode .= $elementNode->getInnerHtml();

		if ($tplEngine->isFollowedBy($elementNode, ['else']) === false) {
			$phpCode .= '<?php } ?>';
		}

		$textNode = new TextNode();
		$textNode->content = $phpCode;

		$elementNode->parentNode->replaceNode($elementNode, $textNode);
		$elementNode->parentNode->removeNode($elementNode);
	}
}