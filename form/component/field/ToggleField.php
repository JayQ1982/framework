<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use LogicException;
use framework\form\component\FormField;
use framework\form\FormComponent;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;
use framework\form\rule\RequiredRule;

class ToggleField extends OptionsField
{
	private array $childrenByMainOption = [];
	private string $defaultChildFieldRenderer = '\framework\form\renderer\DefinitionListRenderer';
	private bool $displayLegend;
	private bool $multiple;
	private ?string $listDescription = null;

	public function __construct(string $name, string $label, array $options, bool $optionsAreHTML, $value, ?string $requiredError = null, bool $displayLegend = true, bool $multiple = false)
	{
		$this->multiple = $multiple;

		if ($multiple) {
			$value = $this->changeValueToArray($value);
		}
		$this->displayLegend = $displayLegend;

		parent::__construct($name, $label, $options, $optionsAreHTML, $value);

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}
	}

	public function setListDescription(?string $listDescription): void
	{
		$this->listDescription = $listDescription;
	}

	public function setDefaultChildFieldRenderer(string $rendererName): void
	{
		$this->defaultChildFieldRenderer = $rendererName;
	}

	/**
	 * @param string|int $mainOption
	 * @param FormField  $childField
	 *
	 * @throws LogicException
	 */
	public function addChildField($mainOption, FormField $childField): void
	{
		$this->addChildComponent($mainOption, $childField);
	}

	public function addChildComponent($mainOption, FormComponent $childComponent): void
	{
		if (!isset($this->getOptions()[$mainOption])) {
			throw new LogicException('The mainOption ' . $mainOption . ' does not exist!');
		}
		$childComponent->setParentFormComponent($this);

		$this->childrenByMainOption[$mainOption][$childComponent->getName()] = $childComponent;
	}

	/**
	 * @param string|int $mainOption
	 * @param string     $fieldName
	 *
	 * @return FormField
	 */
	public function getChildField($mainOption, string $fieldName): FormField
	{
		$childField = $this->getChildComponent($mainOption, $fieldName);
		if (($childField instanceof FormField) === false) {
			throw new LogicException('The childField ' . $fieldName . ' of mainOption ' . $mainOption . ' is not an instance of FormField');
		}

		return $childField;
	}

	/**
	 * @param string|int $mainOption
	 * @param string     $componentName
	 *
	 * @return FormComponent|FormField
	 */
	public function getChildComponent($mainOption, string $componentName)
	{
		if (!isset($this->childrenByMainOption[$mainOption][$componentName])) {
			throw new LogicException('The mainOption ' . $mainOption . ' has no child ' . $componentName);
		}

		$childComponent = $this->childrenByMainOption[$mainOption][$componentName];

		if (($childComponent instanceof FormComponent) === false) {
			throw new LogicException('The child ' . $componentName . ' of mainOption ' . $mainOption . ' is not an instance of FormComponent');
		}

		return $childComponent;
	}

	public function getChildrenByMainOption(): array
	{
		return $this->childrenByMainOption;
	}

	public function getFormTag(): FormTag
	{
		$ulTag = new FormTag('ul', false, [new FormTagAttribute('class', 'form-toggle-list')]);

		// Iterate through every selectable option of the ToggleField
		$optionsAreHTML = $this->isOptionsHTML();
		foreach ($this->getOptions() as $key => $val) {

			$combinedSpecifier = $this->getName() . '_' . $key;

			// ... create from inner to outer tag ...

			// Define the attributes for the <input> element:
			$inputAttributes = [
				new FormTagAttribute('type', $this->multiple ? 'checkbox' : 'radio'),
				new FormTagAttribute('toggle-id', $combinedSpecifier),
				new FormTagAttribute('name', $this->multiple ? $this->getName() . '[]' : $this->getName()),
				new FormTagAttribute('value', $key),
			];

			if (isset($this->childrenByMainOption[$key])) {
				$inputAttributes[] = new FormTagAttribute('aria-describedby', $combinedSpecifier);
			}
			// If that option has been selected, mark it as such with an extra attribute:
			if ($this->multiple) {
				if (in_array($key, $this->getRawValue())) {
					$inputAttributes[] = new FormTagAttribute('checked', null);
				}
			} else {
				if ((string)$key === (string)$this->getRawValue()) {
					$inputAttributes[] = new FormTagAttribute('checked', null);
				}
			}
			// Create the Toggle-<input>
			$input = new FormTag('input', true, $inputAttributes);

			// Create the Toggle-<label> element:
			$label = new FormTag('label', false);
			// add the Toggle-<input> into Toggle-<label>
			$label->addTag($input);
			$label->addText(new FormText(' ' . $val, $optionsAreHTML));

			// create -Toggle-<li> tag and add the Toggle-<label> to it
			$li = new FormTag('li', false);
			$li->addTag($label);

			// Now add the child fields to that Toggle-<li>-Option:
			if (isset($this->childrenByMainOption[$key])) {
				$div = new FormTag('div', false, [
					new FormTagAttribute('class', 'form-toggle-content'),
					new FormTagAttribute('id', $combinedSpecifier),
				]);
				/** @var FormField $childField */
				foreach ($this->childrenByMainOption[$key] as $childField) {

					$componentRenderer = $childField->getRenderer();
					if (is_null($componentRenderer)) {

						if ($childField instanceof FormField) {
							$childComponentRenderer = new $this->defaultChildFieldRenderer($childField);
						} else {
							$childComponentRenderer = $childField->getDefaultRenderer();
						}
						$childField->setRenderer($childComponentRenderer);
					}
					// Add the child field into the <div>
					$div->addTag($childField->getFormTag());
				}
				// Add the <div> with the collected child elements to the <li>-Option
				$li->addTag($div);
			}

			$ulTag->addTag($li);
		}

		// If the ToggleField-Area should NOT have an own "label" named "legend",
		// then only the ToggleField-Area will be returned:
		if (!$this->displayLegend) {
			$divClasses = ['form-element'];
			if ($this->hasErrors()) {
				$divClasses[] = 'has-error';
			}
			$divTag = new FormTag('div', false, [new FormTagAttribute('class', implode(' ', $divClasses))]);
			$divTag->addTag($ulTag);
			$divTag = $this->renderErrors($divTag);

			return $divTag;
		}

		$labelText = $this->getLabel();

		// A legend is desired left beside the ToggleField-Area:
		$legendAttributes = [];
		if (!$this->isRenderLabel()) {
			$legendAttributes[] = new FormTagAttribute('class', 'visuallyhidden');
		}

		$legendTag = new FormTag('legend', false, $legendAttributes);
		$legendTag->addText(new FormText($labelText));

		if ($this->isRequired() && $this->isRenderRequiredAbbr()) {
			$abbrTag = new FormTag('abbr', false, [
				new FormTagAttribute('title', 'Erforderliche Eingabe'),
				new FormTagAttribute('class', 'required'),
			]);
			$abbrTag->addText(new FormText('*'));
			$legendTag->addTag($abbrTag);
		}

		$labelInfoText = trim($this->getLabelInfoText());
		if ($labelInfoText !== '') {
			$labelInfoTag = new FormTag('i', false, [
				new FormTagAttribute('class', 'legend-info'),
			]);
			$labelInfoTag->addText(new FormText($labelInfoText));
			$legendTag->addTag($labelInfoTag);
		}

		$attributes = [new FormTagAttribute('class', 'legend-and-list' . ($this->hasErrors() ? ' has-error' : ''))];
		$ariaDescribedBy = [];

		if ($this->hasErrors()) {
			$attributes[] = new FormTagAttribute('aria-invalid', 'true');
			$ariaDescribedBy[] = $this->getName() . '-error';
		}

		if (!is_null($this->getFieldInfoAsHTML())) {
			$ariaDescribedBy[] = $this->getName() . '-info';
		}
		if (count($ariaDescribedBy) > 0) {
			$attributes[] = new FormTagAttribute('aria-describedby', implode(' ', $ariaDescribedBy));
		}

		$fieldsetTag = new FormTag('fieldset', false, $attributes);
		$fieldsetTag->addTag($legendTag);

		if (!is_null($this->listDescription)) {
			$fieldsetTag->addText(new FormText('<div class="fieldset-info">' . FormRenderer::htmlEncode($this->listDescription) . '</div>', true));
		}

		//  the <ul> tag will now be attached
		$fieldsetTag->addTag($ulTag);
		$fieldsetTag = $this->renderErrors($fieldsetTag);

		return $fieldsetTag;
	}

	protected function renderErrors(FormTag $formTag): FormTag
	{
		if (!$this->hasErrors()) {
			return $formTag;
		}

		$bTag = new FormTag('b', false);
		$errorsHTML = [];
		foreach ($this->getErrors() as $msg) {
			$errorsHTML[] = FormRenderer::htmlEncode($msg);
		}
		$bTag->addText(new FormText(implode('<br>', $errorsHTML), true));

		$divTag = new FormTag('div', false, [
			new FormTagAttribute('class', 'form-input-error'),
			new FormTagAttribute('id', $this->getName() . '-error'),
		]);
		$divTag->addTag($bTag);
		$formTag->addTag($divTag);

		return $formTag;
	}

	public function validate(array $inputData, bool $overwriteValue = true): bool
	{
		// First execute main rules on the toggle field
		parent::validate($inputData, $overwriteValue);
		if ($this->hasErrors()) {
			// If we already have an error, return false
			return false;
		}

		$valueAfterValidation = $this->getRawValue();

		// If there is no error so far, we also validate the child fields
		foreach ($this->childrenByMainOption as $mainOption => $children) {
			if ($this->multiple) {
				if (!in_array($mainOption, $valueAfterValidation)) {
					continue;
				}
			} else if ($mainOption != $valueAfterValidation) {
				continue;
			}

			/** @var FormField|FormComponent $formField */
			foreach ($children as $formField) {

				if (($formField instanceof FormField) === false) {
					continue;
				}

				$formField->validate($inputData, $overwriteValue);
			}
		}

		return !$this->hasErrors();
	}

	/**
	 * @param null|array|string $value : Internally we handle the value as array if it is a multiple field
	 *
	 * @return array|null
	 */
	private function changeValueToArray($value = null): ?array
	{
		if (!is_array($value)) {
			return [$value];
		}

		return $value;
	}

	public function setValue($value): void
	{
		if ($this->multiple) {
			$value = $this->changeValueToArray($value);
		}
		parent::setValue($value);
	}
}
/* EOF */