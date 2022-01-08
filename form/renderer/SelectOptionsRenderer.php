<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\form\renderer;

use LogicException;
use framework\form\component\field\SelectOptionsField;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;

class SelectOptionsRenderer extends FormRenderer
{
	private SelectOptionsField $selectOptionsField;
	private bool $chosen = false;
	private bool $multi = false;

	public function __construct(SelectOptionsField $selectOptionsField)
	{
		$this->selectOptionsField = $selectOptionsField;
	}

	public function prepare(): void
	{
		$selectOptionsField = $this->selectOptionsField;
		$options = $selectOptionsField->getFormOptions()->getData();

		$fieldName = $selectOptionsField->getName();
		$selectAttributes = [
			new HtmlTagAttribute('name', $this->multi ? $fieldName . '[]' : $fieldName, true),
			new HtmlTagAttribute('id', $selectOptionsField->getId(), true),
		];

		if ($this->chosen) {
			$selectAttributes[] = new HtmlTagAttribute('class', 'chosen', true);
		}
		if ($this->multi) {
			$selectAttributes[] = new HtmlTagAttribute('multiple', null, true);
		}

		$selectTag = new HtmlTag('select', false, $selectAttributes);

		if ($selectOptionsField->isRenderEmptyValueOption() && !array_key_exists('', $options)) {
			$options = ['' => $selectOptionsField->getEmptyValueLabel()] + $options;
		}
		$selectedValue = $selectOptionsField->getRawValue();
		if ($this->multi && !is_array($selectedValue)) {
			throw new LogicException('The selected value must be an array if selection of multiple elements is allowed');
		}

		$this->prepareOptionsDisplay($selectTag, $options, $selectedValue);

		$this->setHtmlTag($selectTag);
	}

	private function prepareOptionsDisplay(HtmlTag $parentTag, array $options, array|null|int|string $selectedValue): void
	{
		foreach ($options as $key => $val) {
			if (is_array($val)) {
				$childTag = new HtmlTag('optgroup', false, [new HtmlTagAttribute('label', $key, true)]);
				$this->prepareOptionsDisplay($childTag, $val, $selectedValue);
			} else {
				$attributes = [new HtmlTagAttribute('value', $key, true)];
				if (($this->multi && in_array($key, $selectedValue)) || (!$this->multi && $key == $selectedValue)) {
					$attributes[] = new HtmlTagAttribute('selected', null, true);
				}

				$childTag = new HtmlTag('option', false, $attributes);
				$childTag->addText($val);
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
}