<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\core\HttpRequest;
use framework\datacheck\Sanitizer;
use LogicException;

class OptionsFilterField extends AbstractTableFilterField
{
	/** @var FilterOption[] */
	private readonly array $options;
	private string $selectedValue = '';

	public function __construct(
		TableFilter           $parentFilter,
		string                $identifier,
		string                $label,
		array                 $options,
		private readonly string $defaultValue = '',
		private readonly bool $chosenEnhancedDropDown = false
	) {
		parent::__construct(
			parentFilter: $parentFilter,
			filterFieldIdentifier: $identifier,
			label: $label
		);
		$finalOptions = [];
		foreach ($options as $option) {
			if (!($option instanceof FilterOption)) {
				throw new LogicException(message: 'Option must be an instance of FilterOption');
			}
			$finalOptions[$option->identifier] = $option;
		}
		$this->options = $finalOptions;
	}

	public function init(): void
	{
		$this->selectedValue = Sanitizer::trimmedString(input: $this->getFromSession(index: $this->identifier));
	}

	public function reset(): void
	{
		$this->setSelectedValue(selectedValue: $this->defaultValue);
	}

	private function setSelectedValue(string $selectedValue): void
	{
		$this->selectedValue = $selectedValue;
		$this->saveToSession(index: $this->identifier, value: $selectedValue);
	}

	public function checkInput(): void
	{
		$inputValue = Sanitizer::trimmedString(input: HttpRequest::getInputString(keyName: $this->identifier));
		if (array_key_exists($inputValue, $this->options)) {
			$this->setSelectedValue(selectedValue: $inputValue);
		}
	}

	public function getWhereConditions(): array
	{
		return empty($this->selectedValue) ? [] : [$this->options[$this->selectedValue]->sqlCondition];
	}

	public function getSqlParameters(): array
	{
		return empty($this->selectedValue) ? [] : $this->options[$this->selectedValue]->sqlParams;
	}

	protected function renderField(): string
	{
		$filterName = $this->identifier;
		$filterId = 'filter-' . $filterName;

		$html = '';
		if ($this->chosenEnhancedDropDown) {
			$html .= '<select name="' . $filterName . '" id="' . $filterId . '" class="chosen">';
		} else {
			$html .= '<select name="' . $filterName . '" id="' . $filterId . '">';
		}
		foreach ($this->options as $filterOption) {
			$attributes = [
				'option',
				'value="' . $filterOption->identifier . '"',
			];
			if ($filterOption->identifier === $this->selectedValue) {
				$attributes[] = 'selected';
			}

			$html .= '<' . implode(separator: ' ', array: $attributes) . '>' . $filterOption->label . '</option>';
		}
		$html .= '</select>';

		return $html;
	}

	public function isSelected(): bool
	{
		return !empty($this->selectedValue);
	}

	public function getSelectedValue(): string
	{
		return $this->selectedValue;
	}
}