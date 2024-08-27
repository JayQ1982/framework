<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use DateTimeImmutable;
use framework\core\HttpRequest;
use framework\db\DbQueryData;
use framework\html\HtmlText;
use Throwable;

class DateFilterField extends AbstractTableFilterField
{
	private ?DateTimeImmutable $value = null;

	public function __construct(
		TableFilter             $parentFilter,
		string                  $filterFieldIdentifier,
		HtmlText                $label,
		private readonly string $dataTableColumnReference,
		private readonly bool   $dateMustBeSameOrLater,
		private readonly string $renderFormat = 'd.m.Y H:i:s',
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
		$valueFromSession = (string)$this->getFromSession(index: $this->identifier);
		if ($valueFromSession !== '') {
			$this->value = new DateTimeImmutable(datetime: $valueFromSession);
		}
	}

	public function reset(): void
	{
		$this->value = null;
		$this->saveToSession(index: $this->identifier, value: '');
	}

	public function checkInput(): void
	{
		$inputValue = (string)HttpRequest::getInputString(keyName: $this->identifier);
		if ($inputValue === '') {
			$this->reset();

			return;
		}
		try {
			$forceTimePart = '';
			if (!str_contains(haystack: $inputValue, needle: ':')) {
				$forceTimePart = $this->dateMustBeSameOrLater ? ' 00:00:00' : ' 23:59:59';
			}
			$dateTimeObject = new DateTimeImmutable(datetime: $inputValue . $forceTimePart);
			if (DateTimeImmutable::getLastErrors() !== false) {
				$this->reset();

				return;
			}
			$this->value = $dateTimeObject;
			$this->saveToSession(index: $this->identifier, value: $dateTimeObject->format(format: 'Y-m-d H:i:s'));
		} catch (Throwable) {
			$this->reset();
		}
	}

	public function getWhereCondition(): DbQueryData
	{
		return new DbQueryData(
			query: $this->dataTableColumnReference . ($this->dateMustBeSameOrLater ? '>=' : '<=') . '?',
			params: [$this->value->format(format: 'Y-m-d H:i:s')]
		);
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

		return '<input type="text" class="' . implode(separator: ' ', array: $classes) . '" name="' . $this->identifier . '" id="filter-' . $this->identifier . '" value="' . (is_null(value: $this->value) ? '' : $this->value->format(format: $this->renderFormat)) . '">';
	}

	public function isSelected(): bool
	{
		return !is_null(value: $this->value);
	}
}