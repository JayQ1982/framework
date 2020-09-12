<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use LogicException;
use framework\form\component\field\CheckboxOptionsField;
use framework\form\component\field\RadioOptionsField;
use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

class LegendAndListRenderer extends FormRenderer
{
	private FormField $formField;

	public function __construct(FormField $formField)
	{
		if (
			!($formField instanceof CheckboxOptionsField)
			&& !($formField instanceof RadioOptionsField)
		) {
			throw new LogicException('The $formField must be either an instance of CheckboxOptionsField or RadioOptionsField');
		}
		$this->formField = $formField;
	}

	public function prepare(): void
	{
		$formField = $this->formField;

		$labelInfoText = trim($this->formField->getLabelInfoText());
		$labelText = $formField->getLabel();
		if ($labelInfoText !== '') {
			// add a space to separate it from following labelInfo-Tag
			$labelText .= ' ';
		}

		$legendAttributes = [];
		if (!$formField->isRenderLabel()) {
			$legendAttributes[] = new FormTagAttribute('class', 'visuallyhidden');
		}

		$legendTag = new FormTag('legend', false, $legendAttributes);
		$legendTag->addText(new FormText($labelText));

		if ($labelInfoText !== '') {
			$labelInfoTag = new FormTag('i', false, [
				new FormTagAttribute('class', 'legend-info'),
			]);
			$labelInfoTag->addText(new FormText($labelInfoText));
			$legendTag->addTag($labelInfoTag);
		}

		if ($formField->isRequired() && $formField->isRenderRequiredAbbr()) {
			$abbrTag = new FormTag('abbr', false, [
				new FormTagAttribute('title', 'Erforderliche Eingabe'),
				new FormTagAttribute('class', 'required'),
			]);
			$abbrTag->addText(new FormText('*'));
			$legendTag->addTag($abbrTag);
		}

		$attributes = [new FormTagAttribute('class', 'legend-and-list' . ($formField->hasErrors() ? ' has-error' : ''))];
		$ariaDescribedBy = [];

		if ($formField->hasErrors()) {
			$attributes[] = new FormTagAttribute('aria-invalid', 'true');
			$ariaDescribedBy[] = $formField->getName() . '-error';
		}

		if (!is_null($formField->getFieldInfoAsHTML())) {
			$ariaDescribedBy[] = $formField->getName() . '-info';
		}
		if (count($ariaDescribedBy) > 0) {
			$attributes[] = new FormTagAttribute('aria-describedby', implode(' ', $ariaDescribedBy));
		}

		$fieldsetTag = new FormTag('fieldset', false, $attributes);
		$fieldsetTag->addTag($legendTag);

		if ($formField instanceof CheckboxOptionsField) {
			$checkboxOptionsRenderer = new CheckboxOptionsRenderer($formField);
			$checkboxOptionsRenderer->prepare();

			$fieldsetTag->addTag($checkboxOptionsRenderer->getFormTag());
			$fieldsetTag = $this->prepareErrorDisplay($fieldsetTag);
		} else if ($formField instanceof RadioOptionsField) {
			$radioOptionsRenderer = new RadioOptionsRenderer($formField);
			$radioOptionsRenderer->prepare();

			$fieldsetTag->addTag($radioOptionsRenderer->getFormTag());
			$fieldsetTag = $this->prepareErrorDisplay($fieldsetTag);
		}

		if (!is_null($formField->getFieldInfoAsHTML())) {
			$fieldsetTag = $formField->addFieldInfo($fieldsetTag);
		}

		$this->setFormTag($fieldsetTag);
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