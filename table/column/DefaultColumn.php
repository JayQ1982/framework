<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\table\column;

use framework\table\TableItemModel;

class DefaultColumn extends AbstractTableColumn
{
	private bool $renderNewLines = true;

	public function setRenderNewLines(bool $renderNewLines): void
	{
		$this->renderNewLines = $renderNewLines;
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		return $tableItemModel->renderValue($this->getIdentifier(), $this->renderNewLines);
	}
}