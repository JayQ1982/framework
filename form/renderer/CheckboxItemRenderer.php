<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\field\CheckboxOptionsField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

class CheckboxItemRenderer extends FormRenderer
{
	private CheckboxOptionsField $checkboxOptionsField;

	public function __construct(CheckboxOptionsField $checkboxOptionsField)
	{
		$this->checkboxOptionsField = $checkboxOptionsField;
	}

	public function prepare(): void
	{
		$checkboxOptionsField = $this->checkboxOptionsField;

		$formElementClasses = ['form-element'];
		if ($checkboxOptionsField->hasErrors()) {
			$formElementClasses[] = 'has-error';
		}

		$formElementDiv = new FormTag('div', false, [new FormTagAttribute('class', implode(' ', $formElementClasses))]);

		$formItemCheckboxDiv = new FormTag('div', false, [new FormTagAttribute('class', 'form-item-checkbox')]);

		$labelTag = new FormTag('label', false);
		$labelTag->addTag($this->getInputTag());
		$labelTag->addText(new FormText($checkboxOptionsField->getLabel()));
		$formItemCheckboxDiv->addTag($labelTag);
		$formElementDiv->addTag($formItemCheckboxDiv);

		if (!is_null($checkboxOptionsField->getFieldInfoAsHTML())) {
			$formElementDiv = $checkboxOptionsField->addFieldInfo($formElementDiv);
		}

		$formElementDiv = $this->prepareErrorDisplay($formElementDiv);

		$this->setFormTag($formElementDiv);
	}

	private function getInputTag(): FormTag
	{
		$options = $this->checkboxOptionsField->getOptions();
		$optionValue = (string)key($options);
		$attributes = [
			new FormTagAttribute('type', 'checkbox'),
			new FormTagAttribute('name', $this->checkboxOptionsField->getName()),
			new FormTagAttribute('id', $this->checkboxOptionsField->getId()),
			new FormTagAttribute('value', $optionValue),
		];

		$checkboxValue = $this->checkboxOptionsField->getRawValue();

		if (is_scalar($checkboxValue) && (string)$checkboxValue == $optionValue) {
			$attributes[] = new FormTagAttribute('checked', null);
		}

		$ariaDescribedBy = [];

		if ($this->checkboxOptionsField->hasErrors()) {
			$attributes[] = new FormTagAttribute('aria-invalid', 'true');
			$ariaDescribedBy[] = $this->checkboxOptionsField->getName() . '-error';
		}

		if (!is_null($this->checkboxOptionsField->getFieldInfoAsHTML())) {
			$ariaDescribedBy[] = $this->checkboxOptionsField->getName() . '-info';
		}

		if (count($ariaDescribedBy) > 0) {
			$attributes[] = new FormTagAttribute('aria-describedby', implode(' ', $ariaDescribedBy));
		}

		return new FormTag('input', true, $attributes);
	}

	protected function prepareErrorDisplay(FormTag $formTag): FormTag
	{
		$checkboxOptionsField = $this->checkboxOptionsField;

		if (!$checkboxOptionsField->hasErrors()) {
			return $formTag;
		}

		$bTag = new FormTag('b', false);
		$errorsHTML = [];
		foreach ($checkboxOptionsField->getErrors() as $msg) {
			$errorsHTML[] = FormRenderer::htmlEncode($msg);
		}
		$bTag->addText(new FormText(implode('<br>', $errorsHTML), true));

		$divTag = new FormTag('div', false, [
			new FormTagAttribute('class', 'form-input-error'),
			new FormTagAttribute('id', $checkboxOptionsField->getName() . '-error'),
		]);
		$divTag->addTag($bTag);
		$formTag->addTag($divTag);

		return $formTag;
	}
}
/* EOF */