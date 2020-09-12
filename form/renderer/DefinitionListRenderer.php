<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

class DefinitionListRenderer extends FormRenderer
{
	protected FormField $formField;

	public function __construct(FormField $formField)
	{
		$this->formField = $formField;
	}

	public function prepare(): void
	{
		$formField = $this->formField;

		$labelInfoText = trim($this->formField->getLabelInfoText());
		$labelText = $formField->getLabel();

		$labelAttributes = [new FormTagAttribute('for', $formField->getName())];
		if (!$this->formField->isRenderLabel()) {
			$labelAttributes[] = new FormTagAttribute('class', 'visuallyhidden');
		}

		$labelTag = new FormTag('label', false, $labelAttributes);
		$labelTag->addText(new FormText($labelText));

		if ($formField->isRequired() && $formField->isRenderRequiredAbbr()) {
			$abbrTag = new FormTag('abbr', false, [
				new FormTagAttribute('title', 'Erforderliche Eingabe'),
				new FormTagAttribute('class', 'required'),
			]);
			$abbrTag->addText(new FormText('*'));
			$labelTag->addTag($abbrTag);
		}

		if ($labelInfoText !== '') {
			$labelInfoTag = new FormTag('i', false, [
				new FormTagAttribute('class', 'label-info'),
			]);
			$labelInfoTag->addText(new FormText($labelInfoText, true));
			$labelTag->addTag($labelInfoTag);
		}

		if (!$this->formField->isRenderLabel()) {
			// A <div> (instead of <dd>) will be created to contain the child with the "visualInvisible" <label>
			$divTag = new FormTag('div', false);
			$divTag->addTag($labelTag);
			if ($formField->hasErrors()) {
				$divTag->addFormTagAttribute(new FormTagAttribute('class', 'form-toggle-content-item has-error'));
			} else {
				$divTag->addFormTagAttribute(new FormTagAttribute('class', 'form-toggle-content-item'));
			}
			$defaultFormFieldRenderer = $formField->getDefaultRenderer();
			$defaultFormFieldRenderer->prepare();
			$divTag->addTag($defaultFormFieldRenderer->getFormTag());
			$divTag = $this->prepareErrorDisplay($divTag);
			if (!is_null($formField->getFieldInfoAsHTML())) {
				$divTag = $formField->addFieldInfo($divTag);
			}
			$this->setFormTag($divTag);

			return;
		}

		// Show WITH label, therefore <dl><dt><dd>-Frame is required:
		$dtTag = new FormTag('dt', false);

		$dtTag->addTag($labelTag);

		$additionalColumnContent = $formField->getAdditionalColumnContent();

		$ddAttributes = [];
		if (!is_null($additionalColumnContent)) {
			$ddAttributes[] = new FormTagAttribute('class', 'form-cols');
		}

		$ddTag = new FormTag('dd', false, $ddAttributes);
		if ($formField->hasErrors()) {
			$ddTag->addFormTagAttribute(new FormTagAttribute('class', 'has-error'));
		}

		$defaultFormFieldRenderer = $formField->getDefaultRenderer();
		$defaultFormFieldRenderer->prepare();

		if (!is_null($additionalColumnContent)) {
			$column1 = new FormTag('div', false, [new FormTagAttribute('class', 'form-col-1')]);
			$column1->addTag($defaultFormFieldRenderer->getFormTag());
			$ddTag->addTag($column1);

			$column2 = new FormTag('div', false, [new FormTagAttribute('class', 'form-col-2')]);
			$column2->addText(new FormText($additionalColumnContent));
			$ddTag->addTag($column2);
		} else {
			$ddTag->addTag($defaultFormFieldRenderer->getFormTag());
		}

		$ddTag = $this->prepareErrorDisplay($ddTag);

		if (!is_null($formField->getFieldInfoAsHTML())) {
			$ddTag = $formField->addFieldInfo($ddTag);
		}

		$dlTag = new FormTag('dl', false, [new FormTagAttribute('class', 'clearfix')]);
		$dlTag->addTag($dtTag);
		$dlTag->addTag($ddTag);
		$this->setFormTag($dlTag);
	}

	protected function prepareErrorDisplay(FormTag $formTag): FormTag
	{
		$formField = $this->formField;
		if (!$formField->hasErrors()) {
			return $formTag;
		}

		$bTag = new FormTag('b', false);
		$errorsHTML = [];
		foreach ($formField->getErrors() as $msg) {
			$errorsHTML[] = FormRenderer::htmlEncode($msg);
		}
		$bTag->addText(new FormText(implode('<br>', $errorsHTML), true));

		$divTag = new FormTag('div', false, [
			new FormTagAttribute('class', 'form-input-error'),
			new FormTagAttribute('id', $formField->getName() . '-error'),
		]);
		$divTag->addTag($bTag);
		$formTag->addTag($divTag);

		return $formTag;
	}
}
/* EOF */