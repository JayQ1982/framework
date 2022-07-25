<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
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
		$value = $tableItemModel->getRawValue(name: $this->identifier);

		if (is_null(value: $value)) {
			return '';
		}

		if ($value === 1 || $value === true) {
			return $this->trueLabel;
		}

		if ($value === 0 || $value === false) {
			return $this->falseLabel;
		}

		return $tableItemModel->renderValue(name: $this->identifier);
	}
}