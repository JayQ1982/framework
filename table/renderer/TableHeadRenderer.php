<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
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

		return implode(PHP_EOL, [
			'<tr>',
			implode(PHP_EOL, $columns),
			'</tr>',
		]);
	}

	protected function renderColumnHead(AbstractTableColumn $abstractTableColumn): string
	{
		$attributesArr = ['th'];
		if ($this->addColumnScopeAttribute) {
			$attributesArr[] = 'scope="col"';
		}

		return '<' . implode(' ', $attributesArr) . '>' . $abstractTableColumn->getLabel() . '</th>';
	}
}