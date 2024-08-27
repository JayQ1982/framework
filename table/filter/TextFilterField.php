<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\common\SearchHelper;
use framework\core\HttpRequest;
use framework\db\DbQueryData;
use framework\html\HtmlEncoder;
use framework\html\HtmlText;

class TextFilterField extends AbstractTableFilterField
{
	protected string $value = '';

	public function __construct(
		TableFilter             $parentFilter,
		string                  $filterFieldIdentifier,
		HtmlText                $label,
		private readonly string $dataTableColumnReference,
		bool                    $highlightFieldIfSelected = false
	) {
		parent::__construct(
			parentFilter: $parentFilter,
			filterFieldIdentifier: $filterFieldIdentifier,
			label: $label,
			highlightFieldIfSelected: $highlightFieldIfSelected
		);
	}

	public function init(): void
	{
		$this->value = (string)$this->getFromSession(index: $this->identifier);
	}

	public function reset(): void
	{
		$this->setValue(value: '');
	}

	protected function setValue(string $value): void
	{
		$this->value = $value;
		$this->saveToSession(index: $this->identifier, value: $value);
	}

	public function checkInput(): void
	{
		$this->setValue(value: (string)HttpRequest::getInputString(keyName: $this->identifier));
	}

	public function getWhereCondition(): DbQueryData
	{
		return SearchHelper::createSQLFilters(filterArr: [
			preg_replace(
				pattern: '!\s+!',
				replacement: ' ',
				subject: $this->dataTableColumnReference
			) => $this->value,
		]);
	}

	protected function renderField(): string
	{
		$classes = ['text'];
		if (
			$this->highlightFieldIfSelected
			&& $this->isSelected()
		) {
			$classes[] = 'highlight';
		}

		return '<input type="text" class="' . implode(separator: ' ', array: $classes) . '" name="' . $this->identifier . '" id="filter-' . $this->identifier . '" value="' . HtmlEncoder::encode(value: $this->value) . '">';
	}

	public function isSelected(): bool
	{
		return ($this->value !== '');
	}

	public function getValue(): string
	{
		return $this->value;
	}
}