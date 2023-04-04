<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\renderer;

use framework\form\component\field\OptionsField;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;

class LegendAndListRenderer extends FormRenderer
{
	public function __construct(private readonly OptionsField $optionsField) { }

	public function prepare(): void
	{
		$optionsField = $this->optionsField;
		$labelInfoText = $optionsField->getLabelInfoText();
		$labelText = $optionsField->getLabel();
		if (!is_null(value: $labelInfoText)) {
			// Add a space to separate it from following labelInfo-Tag
			$labelText = HtmlText::encoded(textContent: ' ' . $labelText->render());
		}
		$legendAttributes = [];
		if (!$optionsField->isRenderLabel()) {
			$legendAttributes[] = new HtmlTagAttribute(name: 'class', value: 'visuallyhidden', valueIsEncodedForRendering: true);
		}
		$legendTag = new HtmlTag(name: 'legend', selfClosing: false, htmlTagAttributes: $legendAttributes);
		$legendTag->addText(htmlText: $labelText);
		if (!is_null(value: $labelInfoText)) {
			$labelInfoTag = new HtmlTag(name: 'i', selfClosing: false, htmlTagAttributes: [
				new HtmlTagAttribute(name: 'class', value: 'legend-info', valueIsEncodedForRendering: true),
			]);
			$labelInfoTag->addText(htmlText: $labelInfoText);
			$legendTag->addTag(htmlTag: $labelInfoTag);
		}
		if ($optionsField->isRequired() && $optionsField->isRenderRequiredAbbr()) {
			$spanTag = new HtmlTag(name: 'span', selfClosing: false, htmlTagAttributes: [
				new HtmlTagAttribute(name: 'class', value: 'required', valueIsEncodedForRendering: true),
			]);
			$spanTag->addText(htmlText: HtmlText::encoded(textContent: '*'));
			$legendTag->addTag(htmlTag: $spanTag);
		}
		$fieldsetTag = LegendAndListRenderer::createFieldsetTag(optionsField: $optionsField);
		$fieldsetTag->addTag(htmlTag: $legendTag);
		$listDescription = $optionsField->getListDescription();
		if (!is_null(value: $listDescription)) {
			$fieldsetTag->addText(htmlText: HtmlText::encoded(textContent: '<div class="fieldset-info">' . $listDescription->render() . '</div>'));
		}
		$defaultFormFieldRenderer = $optionsField->getDefaultRenderer();
		$defaultFormFieldRenderer->prepare();
		$fieldsetTag->addTag(htmlTag: $defaultFormFieldRenderer->getHtmlTag());
		FormRenderer::addErrorsToParentHtmlTag(formComponentWithErrors: $optionsField, parentHtmlTag: $fieldsetTag);
		if (!is_null(value: $optionsField->getFieldInfo())) {
			FormRenderer::addFieldInfoToParentHtmlTag(formFieldWithFieldInfo: $optionsField, parentHtmlTag: $fieldsetTag);
		}
		$this->setHtmlTag(htmlTag: $fieldsetTag);
	}

	public static function createFieldsetTag(OptionsField $optionsField): HtmlTag
	{
		$fieldsetTag = new HtmlTag(
			name: 'fieldset',
			selfClosing: false,
			htmlTagAttributes: [
				new HtmlTagAttribute(
					name: 'class',
					value: 'legend-and-list',
					valueIsEncodedForRendering: true
				),
			]
		);
		FormRenderer::addAriaAttributesToHtmlTag(formField: $optionsField, parentHtmlTag: $fieldsetTag);

		return $fieldsetTag;
	}
}