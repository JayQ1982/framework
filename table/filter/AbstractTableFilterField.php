<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use LogicException;
use framework\table\table\DbResultTable;

abstract class AbstractTableFilterField
{
	private const sessionDataType = 'columnFilter';

	/** @var AbstractTableFilterField[] */
	private static array $instances = [];
	private string $identifier;

	protected function __construct(string $identifier)
	{
		if (array_key_exists(key: $identifier, array: AbstractTableFilterField::$instances)) {
			throw new LogicException(message: 'There is already a column filter with the same identifier ' . $identifier);
		}
		$this->identifier = $identifier;
		AbstractTableFilterField::$instances[$identifier] = $this;
	}

	protected function getFromSession(string $index): ?string
	{
		return DbResultTable::getFromSession(dataType: AbstractTableFilterField::sessionDataType, identifier: $this->getIdentifier(), index: $index);
	}

	protected function saveToSession(string $index, string $value): void
	{
		DbResultTable::saveToSession(dataType: AbstractTableFilterField::sessionDataType,identifier:  $this->getIdentifier(), index: $index, value: $value);
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