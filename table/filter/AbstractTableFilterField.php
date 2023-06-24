<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\html\HtmlDataObject;
use framework\table\table\DbResultTable;
use LogicException;

abstract class AbstractTableFilterField
{
	private const sessionDataType = 'columnFilter';

	/** @var AbstractTableFilterField[] */
	private static array $instances = [];
	public readonly string $identifier;

	protected function __construct(
		TableFilter             $parentFilter,
		string                  $filterFieldIdentifier,
		private readonly string $label
	) {
		$uniqueIdentifier = $parentFilter->identifier . '_' . $filterFieldIdentifier;
		if (array_key_exists(key: $uniqueIdentifier, array: AbstractTableFilterField::$instances)) {
			throw new LogicException(message: 'There is already a column filter with the same identifier ' . $uniqueIdentifier);
		}
		$this->identifier = $uniqueIdentifier;
		AbstractTableFilterField::$instances[$uniqueIdentifier] = $this;
	}

	protected function getFromSession(string $index): ?string
	{
		return DbResultTable::getFromSession(dataType: AbstractTableFilterField::sessionDataType, identifier: $this->identifier, index: $index);
	}

	protected function saveToSession(string $index, string $value): void
	{
		DbResultTable::saveToSession(dataType: AbstractTableFilterField::sessionDataType, identifier: $this->identifier, index: $index, value: $value);
	}

	public function render(): HtmlDataObject
	{
		$field = new HtmlDataObject();
		$field->addBooleanValue(propertyName: 'highlight', booleanValue: $this->isSelected());
		$field->addTextElement(propertyName: 'label', content: $this->label, isEncodedForRendering: true);
		$field->addTextElement(propertyName: 'html', content: $this->renderField(), isEncodedForRendering: true);

		return $field;
	}

	abstract protected function renderField(): string;

	abstract public function isSelected(): bool;

	abstract public function init(): void;

	abstract public function reset(): void;

	abstract public function checkInput(): void;

	abstract public function getWhereConditions(): array;

	abstract public function getSqlParameters(): array;
}