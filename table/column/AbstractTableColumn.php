<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\column;

use framework\table\TableItemModel;

abstract class AbstractTableColumn
{
	private const EMPTY_STRING = '';
	private array $columnCssClasses = [];
	private array $cellCssClasses = [];
	private ?string $tableIdentifier = null;

	public function __construct(
		public readonly string $identifier,
		public readonly string $label,
		public readonly bool   $isSortable = false,
		public readonly bool   $sortAscendingByDefault = true
	) {
		if ($this->isSortable) {
			$this->addColumnCssClass(className: 'sort');
		}
	}

	public function setTableIdentifier(string $tableIdentifier): void
	{
		$this->tableIdentifier = $tableIdentifier;
	}

	public function getTableIdentifier(): ?string
	{
		return $this->tableIdentifier;
	}

	public function addColumnCssClass(string $className): void
	{
		if (in_array(needle: $className, haystack: $this->columnCssClasses)) {
			return;
		}
		$this->columnCssClasses[] = $className;
	}

	public function getColumnCssClasses(): array
	{
		return $this->columnCssClasses;
	}

	public function addCellCssClass(string $className): void
	{
		if (in_array(needle: $className, haystack: $this->cellCssClasses)) {
			return;
		}
		$this->cellCssClasses[] = $className;
	}

	public function renderCell(TableItemModel $tableItemModel): string
	{
		$attributesArr = ['td'];
		if (count(value: $this->cellCssClasses) > 0) {
			$attributesArr[] = 'class="' . implode(separator: ' ', array: $this->cellCssClasses) . '"';
		}

		return implode(separator: AbstractTableColumn::EMPTY_STRING, array: [
			'<' . implode(separator: ' ', array: $attributesArr) . '>',
			$this->renderCellValue(tableItemModel: $tableItemModel),
			'</td>',
		]);
	}

	abstract protected function renderCellValue(TableItemModel $tableItemModel): string;
}