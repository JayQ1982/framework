<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\template\customtags;

use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;
use framework\template\template\TagNode;
use framework\template\template\TemplateEngine;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
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
		$tplEngine->checkRequiredAttributes($elementNode, ['options', 'checked']);

		$checkedSelector = $elementNode->getAttribute('checked')->getValue();
		$optionsSelector = $elementNode->getAttribute('options')->getValue();
		$fldName = $elementNode->getAttribute('name')->getValue() . '[]';

		$textContent = '<?php print ' . __CLASS__ . '::render($this, \''.$fldName.'\', \''.$optionsSelector.'\', \''.$checkedSelector.'\'); ?>';

		$newNode = new TextNode();
		$newNode->content = $textContent;

		$elementNode->parentNode->insertBefore($newNode, $elementNode);
		$elementNode->parentNode->removeNode($elementNode);
	}

	public static function render(TemplateEngine $tplEngine, $fldName, $optionsSelector, $checkedSelector): string
	{
		$options = $tplEngine->getDataFromSelector($optionsSelector);
		$selection = (array)$tplEngine->getDataFromSelector($checkedSelector);

		$html = '<ul>';

		foreach ($options as $key => $val) {
			$inputAttributes = [
				'type="checkbox"',
				'value="' . $key . '"',
				'name="' . $fldName . '"',
			];

			if (in_array($key, $selection)) {
				$inputAttributes[] = 'checked';
			}
			// Create inner "span-label":
			$spanLabelTag = new HtmlTag('span', false, [new HtmlTagAttribute('class', 'label-text', true)]);
			$spanLabelTag->addText(HtmlText::encoded($val));
			$html .= '<li><label><input ' . implode(' ', $inputAttributes) . '> ' . $spanLabelTag->render() . '</label></li>' . PHP_EOL;
		}

		$html .= '</ul>';

		return $html;
	}
}