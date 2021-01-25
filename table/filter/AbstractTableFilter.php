<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\table\filter;

use LogicException;
use framework\table\table\DbResultTable;

abstract class AbstractTableFilter
{
	private const sessionDataType = 'tableFilter';

	/** @var AbstractTableFilter[] */
	private static array $instances = [];
	private string $identifier;
	private bool $filtersApplied = false;

	public function __construct(string $identifier)
	{
		if (array_key_exists($identifier, AbstractTableFilter::$instances)) {
			throw new LogicException('There is already a filter with the same identifier ' . $identifier);
		}
		$this->identifier = $identifier;
	}

	protected function getFromSession(string $index): ?string
	{
		return DbResultTable::getFromSession(AbstractTableFilter::sessionDataType, $this->getIdentifier(), $index);
	}

	protected function saveToSession(string $index, string $value): void
	{
		DbResultTable::saveToSession(AbstractTableFilter::sessionDataType, $this->getIdentifier(), $index, $value);
	}

	public function validate(DbResultTable $dbResultTable): void
	{
		$httpRequest = $dbResultTable->getHttpRequest();
		if ($httpRequest->getInputString('find') === $this->getIdentifier() || !is_null($httpRequest->getInputString('reset'))) {
			$this->reset();
		}
		$this->checkInput($dbResultTable);
		$this->filtersApplied = $this->applyFilters($dbResultTable);
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