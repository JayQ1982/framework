<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\renderer;

use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;

class DefinitionListRenderer extends FormRenderer
{
	private FormField $formField;
	/** @var HtmlTag[] */
	private array $htmlTagsBeforeFormField = [];

	public function __construct(FormField $formField)
	{
		$this->formField = $formField;
	}

	public function prepare(): void
	{
		$formField = $this->formField;

		$labelAttributes = [new HtmlTagAttribute('for', $formField->getName(), true)];
		if (!$this->formField->isRenderLabel()) {
			$labelAttributes[] = new HtmlTagAttribute('class', 'visuallyhidden', true);
		}

		$labelTag = new HtmlTag('label', false, $labelAttributes);
		$labelTag->addText($formField->getLabel());

		if ($formField->isRequired() && $formField->isRenderRequiredAbbr()) {
			$abbrTag = new HtmlTag('span', false, [
				new HtmlTagAttribute('class', 'required', true),
			]);
			$abbrTag->addText(HtmlText::encoded('*'));
			$labelTag->addTag($abbrTag);
		}

		$labelInfoText = $formField->getLabelInfoText();
		if (!is_null($labelInfoText)) {
			$labelInfoTag = new HtmlTag('i', false, [
				new HtmlTagAttribute('class', 'label-info', true),
			]);
			$labelInfoTag->addText($labelInfoText);
			$labelTag->addTag($labelInfoTag);
		}

		if (!$this->formField->isRenderLabel()) {
			// A <div> (instead of <dd>) will be created to contain the child with the "visualInvisible" <label>
			$divTag = new HtmlTag('div', false);
			$divTag->addTag($labelTag);
			if ($formField->hasErrors(withChildElements: true)) {
				$divTag->addHtmlTagAttribute(new HtmlTagAttribute('class', 'form-toggle-content-item has-error', true));
			} else {
				$divTag->addHtmlTagAttribute(new HtmlTagAttribute('class', 'form-toggle-content-item', true));
			}
			$defaultFormFieldRenderer = $formField->getDefaultRenderer();
			$defaultFormFieldRenderer->prepare();
			$divTag->addTag($defaultFormFieldRenderer->getHtmlTag());

			FormRenderer::addErrorsToParentHtmlTag($formField, $divTag);
			if (!is_null($formField->getFieldInfo())) {
				FormRenderer::addFieldInfoToParentHtmlTag($formField, $divTag);
			}
			$this->setHtmlTag($divTag);

			return;
		}

		// Show WITH label, therefore <dl><dt><dd>-Frame is required:
		$dtTag = new HtmlTag('dt', false);

		$dtTag->addTag($labelTag);

		$additionalColumnContent = $formField->getAdditionalColumnContent();

		$ddClasses = [];

		if (!is_null($additionalColumnContent)) {
			$ddClasses[] = 'form-cols';
		}

		if ($formField->hasErrors(withChildElements: true)) {
			$ddClasses[] = 'has-error';
		}

		$ddAttributes = (count($ddClasses) === 0) ? [] : [new HtmlTagAttribute('class', implode(' ', $ddClasses), true)];
		$ddTag = new HtmlTag('dd', false, $ddAttributes);

		foreach ($this->htmlTagsBeforeFormField as $htmlTag) {
			$ddTag->addTag($htmlTag);
		}

		$defaultFormFieldRenderer = $formField->getDefaultRenderer();
		$defaultFormFieldRenderer->prepare();

		if (!is_null($additionalColumnContent)) {
			$column1 = new HtmlTag('div', false, [new HtmlTagAttribute('class', 'form-col-1', true)]);
			$column1->addTag($defaultFormFieldRenderer->getHtmlTag());
			$ddTag->addTag($column1);

			$column2 = new HtmlTag('div', false, [new HtmlTagAttribute('class', 'form-col-2', true)]);
			$column2->addText($additionalColumnContent);
			$ddTag->addTag($column2);
		} else {
			$ddTag->addTag($defaultFormFieldRenderer->getHtmlTag());
		}

		FormRenderer::addErrorsToParentHtmlTag($formField, $ddTag);

		if (!is_null($formField->getFieldInfo())) {
			FormRenderer::addFieldInfoToParentHtmlTag($formField, $ddTag);
		}

		$dlTag = new HtmlTag('dl', false);
		$dlTag->addTag($dtTag);
		$dlTag->addTag($ddTag);
		$this->setHtmlTag($dlTag);
	}

	public function addHtmlTagBeforeFormField(HtmlTag $htmlTag): void
	{
		$this->htmlTagsBeforeFormField[] = $htmlTag;
	}
}