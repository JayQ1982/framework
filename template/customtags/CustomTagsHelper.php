<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\template\customtags;

use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;
use framework\template\htmlparser\ElementNode;
use framework\template\htmlparser\TextNode;
use framework\template\template\TemplateEngine;

class CustomTagsHelper
{
	public static function replaceOptionsNode(TemplateEngine $templateEngine, ElementNode $elementNode, bool $multiple): void
	{
		$templateEngine->checkRequiredAttributes($elementNode, ['options', 'checked']);

		$checkedSelector = $elementNode->getAttribute('checked')->getValue();
		$optionsSelector = $elementNode->getAttribute('options')->getValue();
		if ($multiple) {
			$fldName = $elementNode->getAttribute('name')->getValue() . '[]';
		} else {
			$fldName = $elementNode->getAttribute('name')->getValue();
		}

		$textContent = '<?php print ' . __CLASS__ . '::render($this, \'' . $fldName . '\', \'' . $optionsSelector . '\', \'' . $checkedSelector . '\'); ?>';

		$newNode = new TextNode();
		$newNode->content = $textContent;

		$elementNode->parentNode->insertBefore($newNode, $elementNode);
		$elementNode->parentNode->removeNode($elementNode);
	}

	public static function renderOptionsTag(
		TemplateEngine $templateEngine,
		string         $fieldName,
		string         $optionsSelector,
		string         $checkedSelector,
		bool           $multiple
	): string {
		$options = $templateEngine->getDataFromSelector($optionsSelector);
		$selection = (array)$templateEngine->getDataFromSelector($checkedSelector);

		$html = '<ul>';

		$type = $multiple ? 'checkbox' : 'radio';
		foreach ($options as $key => $val) {
			$inputAttributes = [
				'input',
				'type="' . $type . '"',
				'value="' . $key . '"',
				'name="' . $fieldName . '"',
			];
			if (in_array($key, $selection)) {
				$inputAttributes[] = 'checked';
			}
			// Create inner "span-label":
			$spanLabelTag = new HtmlTag('span', false, [new HtmlTagAttribute('class', 'label-text', true)]);
			$spanLabelTag->addText(HtmlText::encoded($val));
			$html .= '<li><label><' . implode(separator: ' ', array: $inputAttributes) . '> ' . $spanLabelTag->render() . '</label></li>' . PHP_EOL;
		}

		$html .= '</ul>';

		return $html;
	}

	public static function replaceRadioOrCheckboxFieldNode(ElementNode $elementNode, bool $isRadio): void
	{
		$sels = $elementNode->getAttribute(name: 'selection')->getValue();
		$selsStr = '$this->getDataFromSelector(\'' . $sels . '\')';
		$value = $elementNode->getAttribute(name: 'value')->getValue();
		$elementNode->removeAttribute(name: 'selection');

		$elementNode->namespace = null;
		$elementNode->tagName = 'input';
		if ($sels !== null) {
			$elementNode->tagExtension = ' <?php echo ((is_array(' . $selsStr . ') && in_array(' . $value . ', ' . $selsStr . ')) || (' . $selsStr . ' == \'' . $value . '\'))?\' checked\':null; ?>';
		}
		$elementNode->addAttribute(htmlTagAttribute: new HtmlTagAttribute(
			name: 'type',
			value: $isRadio ? 'radio' : 'checkbox',
			valueIsEncodedForRendering: true
		));
	}
}