<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\renderer;

use framework\table\column\AbstractTableColumn;
use framework\table\table\SmartTable;

class TableHeadRenderer
{
	private bool $addColumnScopeAttribute = true;

	protected function isAddColumnScopeAttribute(): bool
	{
		return $this->addColumnScopeAttribute;
	}

	protected function setAddColumnScopeAttribute(bool $addColumnScopeAttribute): void
	{
		$this->addColumnScopeAttribute = $addColumnScopeAttribute;
	}

	public function render(SmartTable $smartTable): string
	{
		$columns = [];

		foreach ($smartTable->getColumns() as $abstractTableColumn) {
			$columns[] = $this->renderColumnHead($abstractTableColumn);
		}

		return implode(separator: PHP_EOL, array: [
			'<tr>',
			implode(separator: PHP_EOL, array: $columns),
			'</tr>',
		]);
	}

	protected function renderColumnHead(AbstractTableColumn $abstractTableColumn): string
	{
		$columnCssClasses = $abstractTableColumn->getColumnCssClasses();
		$attributesArr = ['th'];
		if ($this->addColumnScopeAttribute) {
			$attributesArr[] = 'scope="col"';
		}
		if (count(value: $columnCssClasses) > 0) {
			$attributesArr[] = 'class="' . implode(separator: ' ', array: $columnCssClasses) . '"';
		}

		return '<' . implode(separator: ' ', array: $attributesArr) . '>' . $abstractTableColumn->label . '</th>';
	}
}