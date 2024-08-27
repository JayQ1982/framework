<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\core\HttpRequest;
use framework\db\DbQueryData;
use framework\html\HtmlText;
use LogicException;

class OptionsFilterField extends AbstractTableFilterField
{
	/** @var FilterOption[] */
	private readonly array $filterOptions;
	private string $selectedValue = '';

	public function __construct(
		TableFilter             $parentFilter,
		string                  $filterFieldIdentifier,
		HtmlText                $label,
		array                   $filterOptions,
		private readonly string $defaultValue = '',
		private readonly bool   $chosenEnhancedDropDown = false,
		bool                    $highlightFieldIfSelected = false
	) {
		parent::__construct(
			parentFilter: $parentFilter,
			filterFieldIdentifier: $filterFieldIdentifier,
			label: $label,
			highlightFieldIfSelected: $highlightFieldIfSelected
		);
		$finalOptions = [];
		foreach ($filterOptions as $filterOption) {
			if (!($filterOption instanceof FilterOption)) {
				throw new LogicException(message: 'Option must be an instance of FilterOption');
			}
			$finalOptions[$filterOption->identifier] = $filterOption;
		}
		$this->filterOptions = $finalOptions;
	}

	public function init(): void
	{
		$this->selectedValue = (string)$this->getFromSession(index: $this->identifier);
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
		$inputValue = (string)HttpRequest::getInputString(keyName: $this->identifier);
		if (array_key_exists(key: $inputValue, array: $this->filterOptions)) {
			$this->setSelectedValue(selectedValue: $inputValue);
		}
	}

	public function getWhereCondition(): DbQueryData
	{
		return $this->filterOptions[$this->selectedValue]->whereCondition;
	}

	protected function renderField(): string
	{
		$filterName = $this->identifier;
		$htmlArr = [];
		$classes = [];
		if (
			$this->highlightFieldIfSelected
			&& $this->isSelected()
		) {
			$classes[] = 'highlight';
		}
		if ($this->chosenEnhancedDropDown) {
			$classes[] = 'chosen';
		}
		$htmlArr[] = '<select name="' . $filterName . '" id="filter-' . $filterName . '" class="' . implode(separator: ' ', array: $classes) . '">';
		foreach ($this->filterOptions as $filterOption) {
			$htmlArr[] = $filterOption->render(selectedValue: $this->selectedValue);
		}
		$htmlArr[] = '</select>';

		return implode(separator: PHP_EOL, array: $htmlArr);
	}

	public function isSelected(): bool
	{
		return ($this->selectedValue !== '');
	}

	public function getSelectedValue(): string
	{
		return $this->selectedValue;
	}
}