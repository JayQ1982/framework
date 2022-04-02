<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\core\HttpRequest;
use framework\table\table\DbResultTable;
use LogicException;

abstract class AbstractTableFilter
{
	public const PARAM_RESET = 'reset';
	public const PARAM_FIND = 'find';

	private const sessionDataType = 'tableFilter';

	/** @var AbstractTableFilter[] */
	private static array $instances = [];
	private string $identifier;
	private bool $filtersApplied = false;

	public function __construct(string $identifier)
	{
		if (array_key_exists(key: $identifier, array: AbstractTableFilter::$instances)) {
			throw new LogicException(message: 'There is already a filter with the same identifier ' . $identifier);
		}
		$this->identifier = $identifier;
	}

	protected function getFromSession(string $index): ?string
	{
		return DbResultTable::getFromSession(
			dataType: AbstractTableFilter::sessionDataType,
			identifier: $this->getIdentifier(),
			index: $index
		);
	}

	protected function saveToSession(string $index, string $value): void
	{
		DbResultTable::saveToSession(
			dataType: AbstractTableFilter::sessionDataType,
			identifier: $this->getIdentifier(),
			index: $index,
			value: $value
		);
	}

	public function validate(DbResultTable $dbResultTable): void
	{
		if (!is_null(value: HttpRequest::getInputString(keyName: AbstractTableFilter::PARAM_RESET))) {
			$this->reset();
		}
		if (HttpRequest::getInputString(keyName: AbstractTableFilter::PARAM_FIND) === $this->getIdentifier()) {
			$this->reset();
			$this->checkInput(dbResultTable: $dbResultTable);
		}
		$this->filtersApplied = $this->applyFilters(dbResultTable: $dbResultTable);
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	abstract protected function reset(): void;

	abstract protected function checkInput(DbResultTable $dbResultTable): void;

	abstract protected function applyFilters(DbResultTable $dbResultTable): bool;

	abstract public function render(): string;

	public function isFiltersApplied(): bool
	{
		return $this->filtersApplied;
	}
}