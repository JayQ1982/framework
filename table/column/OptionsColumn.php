<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\column;

use framework\table\TableItemModel;

class OptionsColumn extends AbstractTableColumn
{
	public function __construct(
		string                 $identifier,
		string                 $label,
		private readonly array $options,
		bool                   $isOrderAble,
		bool                   $orderAscending = true
	) {
		parent::__construct(
			identifier: $identifier,
			label: $label,
			isSortable: $isOrderAble,
			sortAscendingByDefault: $orderAscending
		);
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		$rawValue = $tableItemModel->getRawValue($this->identifier);
		if (array_key_exists(key: $rawValue, array: $this->options)) {
			return $this->options[$rawValue];
		}

		return $tableItemModel->renderValue(name: $this->identifier);
	}
}