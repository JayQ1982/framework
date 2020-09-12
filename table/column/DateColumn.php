<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\table\column;

use framework\table\TableItemModel;
use DateTime;

class DateColumn extends AbstractTableColumn
{
	private string $format;

	public function __construct(string $identifier, string $label, bool $isSortable, bool $sortAscendingByDefault = true, string $format = 'd.m.Y H:i:s', bool $columnScope = true)
	{
		$this->format = $format;
		parent::__construct($identifier, $label, $isSortable, $sortAscendingByDefault, $columnScope);
	}

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
/* EOF */