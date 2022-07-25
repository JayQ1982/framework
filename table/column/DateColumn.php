<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\column;

use DateTimeImmutable;
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
		$value = trim(string: (string)$tableItemModel->getRawValue(name: $this->identifier));

		return ($value === '') ? '' : (new DateTimeImmutable(datetime: $value))->format(format: $this->format);
	}
}