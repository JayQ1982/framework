<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\column;

use DateTime;
use framework\table\TableItemModel;

class DateColumn extends AbstractTableColumn
{
	private string $format = 'd.m.Y H:i:s';

	public function setFormat(string $format): void
	{
		$this->format = $format;
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		$value = $tableItemModel->getRawValue($this->getIdentifier());

		return empty($value) ? '' : (new DateTime($value))->format($this->format);
	}
}