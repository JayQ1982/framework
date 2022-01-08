<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\table\column;

use framework\table\TableItemModel;

class OptionsColumn extends AbstractTableColumn
{
	private array $options;

	public function __construct(string $identifier, string $label, array $options, bool $isOrderAble, bool $orderAscending = true)
	{
		$this->options = $options;
		parent::__construct(
			identifier: $identifier,
			label: $label,
			isSortable: $isOrderAble,
			sortAscendingByDefault: $orderAscending
		);
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		$rawValue = $tableItemModel->getRawValue($this->getIdentifier());
		if (array_key_exists(key: $rawValue, array: $this->options)) {
			return $this->options[$rawValue];
		}

		return $tableItemModel->renderValue(name: $this->getIdentifier());
	}
}