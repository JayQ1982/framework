<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\template\customtags;

use framework\template\htmlparser\ElementNode;
use framework\template\template\TagNode;
use framework\template\template\templateEngine;
use framework\template\template\TemplateTag;

class CheckboxTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'checkbox';
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
		CustomTagsHelper::replaceRadioOrCheckboxFieldNode(
			elementNode: $elementNode,
			isRadio: false
		);
	}
}