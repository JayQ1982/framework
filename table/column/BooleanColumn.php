<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\table\column;

use framework\table\TableItemModel;

class BooleanColumn extends AbstractTableColumn
{
	private string $trueLabel = 'Ja';
	private string $falseLabel = 'Nein';

	public function setTrueLabel(string $trueLabel): void
	{
		$this->trueLabel = $trueLabel;
	}

	public function setFalseLabel(string $falseLabel): void
	{
		$this->falseLabel = $falseLabel;
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		$value = $tableItemModel->getRawValue($this->getIdentifier());

		if (is_null($value)) {
			return '';
		}

		if ($value === 1 || $value === true) {
			return $this->trueLabel;
		}

		if ($value === 0 || $value === false) {
			return $this->falseLabel;
		}

		return $tableItemModel->renderValue($this->getIdentifier());
	}
}