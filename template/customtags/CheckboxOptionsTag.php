<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\template\customtags;

use framework\template\htmlparser\ElementNode;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\template\TemplateTag;

class CheckboxOptionsTag extends TemplateTag implements TagNode
{
	public static function getName(): string
	{
		return 'checkboxOptions';
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
		CustomTagsHelper::replaceOptionsNode(templateEngine: $tplEngine, elementNode: $elementNode, multiple: true);
	}

	public static function render(TemplateEngine $tplEngine, $fldName, $optionsSelector, $checkedSelector): string
	{
		return CustomTagsHelper::renderOptionsTag(
			templateEngine: $tplEngine,
			fieldName: $fldName,
			optionsSelector: $optionsSelector,
			checkedSelector: $checkedSelector,
			multiple: true
		);
	}
}