<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\table\table\DbResultTable;
use LogicException;

abstract class AbstractTableFilterField
{
	private const sessionDataType = 'columnFilter';

	/** @var AbstractTableFilterField[] */
	private static array $instances = [];
	private string $identifier;

	protected function __construct(AbstractTableFilter $parentFilter, string $filterFieldIdentifier)
	{
		$uniqueIdentifier = $parentFilter->getIdentifier() . '_' . $filterFieldIdentifier;
		if (array_key_exists(key: $uniqueIdentifier, array: AbstractTableFilterField::$instances)) {
			throw new LogicException(message: 'There is already a column filter with the same identifier ' . $uniqueIdentifier);
		}
		$this->identifier = $uniqueIdentifier;
		AbstractTableFilterField::$instances[$uniqueIdentifier] = $this;
	}

	protected function getFromSession(string $index): ?string
	{
		return DbResultTable::getFromSession(dataType: AbstractTableFilterField::sessionDataType, identifier: $this->getIdentifier(), index: $index);
	}

	protected function saveToSession(string $index, string $value): void
	{
		DbResultTable::saveToSession(dataType: AbstractTableFilterField::sessionDataType, identifier: $this->getIdentifier(), index: $index, value: $value);
	}

	public function render(): string
	{
		return implode(separator: PHP_EOL, array: [
			'<li>',
			'<label' . ($this->highLightLabel() ? ' class="highlight"' : '') . '>',
			$this->renderField(),
			'</label>',
			'</li>',
		]);
	}

	abstract protected function renderField(): string;

	abstract protected function highLightLabel(): bool;

	abstract public function init(): void;

	abstract public function reset(): void;

	abstract public function checkInput(): void;

	abstract public function getWhereConditions(): array;

	abstract public function getSqlParameters(): array;

	public function getIdentifier(): string
	{
		return $this->identifier;
	}
}