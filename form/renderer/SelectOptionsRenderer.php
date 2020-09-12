<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use LogicException;
use framework\form\component\field\SelectOptionsField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

class SelectOptionsRenderer extends FormRenderer
{
	private SelectOptionsField $selectOptionsField;
	private bool $chosen = false;
	private bool $multi = false;
	private ?string $onchange;

	public function __construct(SelectOptionsField $selectOptionsField)
	{
		$this->selectOptionsField = $selectOptionsField;
	}

	public function prepare(): void
	{
		$selectOptionsField = $this->selectOptionsField;
		$options = $selectOptionsField->getOptions();

		$fieldName = $selectOptionsField->getName();
		$selectAttributes = [
			new FormTagAttribute('name', $this->multi ? $fieldName . '[]' : $fieldName),
			new FormTagAttribute('id', $selectOptionsField->getId()),
		];

		if ($this->chosen) {
			$selectAttributes[] = new FormTagAttribute('class', 'chosen');
		}
		if ($this->multi) {
			$selectAttributes[] = new FormTagAttribute('multiple', null);
		}
		if (!is_null($this->onchange)) {
			$selectAttributes[] = new FormTagAttribute('onchange', $this->onchange);
		}
		if (!is_null($selectOptionsField->getSize())) {
			$selectAttributes[] = new FormTagAttribute('size', $selectOptionsField->getSize());
		}

		$selectTag = new FormTag('select', false, $selectAttributes);

		if ($selectOptionsField->hasEmptyValue()) {
			$optionsWithEmptyValueEntry = ['' => $selectOptionsField->getEmptyValueLabel()] + $options;
		} else {
			$optionsWithEmptyValueEntry = $options;
		}
		$selectedValue = $selectOptionsField->getRawValue();
		if ($this->multi && !is_array($selectedValue)) {
			throw new LogicException('The selected value must be an array if selection of multiple elements is allowed');
		}

		$this->prepareOptionsDisplay($selectTag, $optionsWithEmptyValueEntry, $selectedValue);

		$this->setFormTag($selectTag);
	}

	private function prepareOptionsDisplay(FormTag $parentTag, array $options, $selectedValue): void
	{
		foreach ($options as $key => $val) {

			if (is_array($val)) {
				$childTag = new FormTag('optgroup', false, [new FormTagAttribute('label', $key)]);
				$this->prepareOptionsDisplay($childTag, $val, $selectedValue);
			} else {
				$attributes = [new FormTagAttribute('value', $key)];
				if (($this->multi && in_array($key, $selectedValue)) || (!$this->multi && $key == $selectedValue)) {
					$attributes[] = new FormTagAttribute('selected', null);
				}

				$childTag = new FormTag('option', false, $attributes);
				// Force isHTML to false because there are no html-tags allowed in option-tags
				$childTag->addText(new FormText($val, false));
			}
			$parentTag->addTag($childTag);
		}
	}

	/**
	 * Activate the chosen-option which adds a chosen-class to the select field
	 *
	 * @param bool $newValue
	 */
	public function setChosen(bool $newValue)
	{
		$this->chosen = $newValue;
	}

	/**
	 * Activate to allow selection of multiple values
	 *
	 * @param bool $newValue
	 */
	public function setMulti(bool $newValue)
	{
		$this->multi = $newValue;
	}

	public function setOnChange(?string $onChange): void
	{
		$this->onchange = $onChange;
	}
}
/* EOF */