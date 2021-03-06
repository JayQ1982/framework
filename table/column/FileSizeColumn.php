<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\table\column;

use framework\common\StringUtils;
use framework\table\TableItemModel;

class FileSizeColumn extends AbstractTableColumn
{
	private int $decimals = 2;

	public function setDecimals(int $decimals): void
	{
		$this->decimals = $decimals;
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		$bytes = $tableItemModel->getRawValue($this->getIdentifier());
		if (is_null($bytes)) {
			return '';
		}
		return StringUtils::formatBytes($bytes, $this->decimals);
	}
}