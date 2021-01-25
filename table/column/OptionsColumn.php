<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\table\column;

use framework\table\TableItemModel;

class OptionsColumn extends AbstractTableColumn
{
	private array $options;

	public function __construct(string $identifier, string $label, array $options, bool $isOrderAble, bool $orderAscending = true, bool $columnScope = true)
	{
		$this->options = $options;
		parent::__construct($identifier, $label, $isOrderAble, $orderAscending, $columnScope);
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		$rawValue = $tableItemModel->getRawValue($this->getIdentifier());
		if (array_key_exists($rawValue, $this->options)) {
			return $this->options[$rawValue];
		}

		return $tableItemModel->renderValue($this->getIdentifier());
	}
}