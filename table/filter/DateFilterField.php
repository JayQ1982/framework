<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use DateTimeImmutable;
use framework\core\HttpRequest;
use framework\datacheck\Sanitizer;
use Throwable;

class DateFilterField extends AbstractTableFilterField
{
	private ?DateTimeImmutable $value = null;

	public function __construct(
		TableFilter             $parentFilter,
		string                  $identifier,
		string                  $label,
		private readonly string $dataTableColumnReference,
		private readonly bool   $mustBeLaterThan
	) {
		parent::__construct(
			parentFilter: $parentFilter,
			filterFieldIdentifier: $identifier,
			label: $label
		);
	}

	public function init(): void
	{
		$valueFromSession = $this->getFromSession($this->identifier);
		if (!empty($valueFromSession)) {
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
		$inputValue = Sanitizer::trimmedString(input: HttpRequest::getInputString(keyName: $this->identifier));

		if ($inputValue === '') {
			$this->reset();

			return;
		}

		try {
			$forceTimePart = '';
			if (!str_contains(haystack: $inputValue, needle: ':')) {
				$forceTimePart = $this->mustBeLaterThan ? ' 00:00:00' : ' 23:59:59';
			}
			$dateTimeObject = new DateTimeImmutable(datetime: $inputValue . $forceTimePart);
			if (DateTimeImmutable::getLastErrors() !== false) {
				$this->reset();

				return;
			}
			$this->value = $dateTimeObject;
			$this->saveToSession(index: $this->identifier, value: $inputValue);
		} catch (Throwable) {
			$this->reset();
		}
	}

	public function getWhereConditions(): array
	{
		if (is_null(value: $this->value)) {
			return [];
		}

		if ($this->mustBeLaterThan) {
			return [$this->dataTableColumnReference . '>=?'];
		}

		return [$this->dataTableColumnReference . '<=?'];
	}

	public function getSqlParameters(): array
	{
		return is_null(value: $this->value) ? [] : [$this->value->format(format: 'Y-m-d H:i:s')];
	}

	protected function renderField(): string
	{
		$value = is_null(value: $this->value) ? '' : $this->value->format(format: 'd.m.Y H:i:s');

		return '<input type="text" class="text" name="' . $this->identifier . '" id="filter-' . $this->identifier . '" value="' . $value . '">';
	}

	public function isSelected(): bool
	{
		return !is_null(value: $this->value);
	}
}