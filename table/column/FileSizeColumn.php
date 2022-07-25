<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
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
		$bytes = $tableItemModel->getRawValue(name: $this->identifier);
		if (is_null(value: $bytes)) {
			return '';
		}

		return StringUtils::formatBytes(bytes: $bytes, precision: $this->decimals);
	}
}