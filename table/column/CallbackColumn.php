<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\table\column;

use framework\table\TableItemModel;

class CallbackColumn extends AbstractTableColumn
{
	/** @var callable */
	private $callbackFunction;

	public function __construct(string $identifier, string $label, callable $callbackFunction, bool $isSortable = false, bool $sortAscendingByDefault = true, bool $columnScope = true)
	{
		$this->callbackFunction = $callbackFunction;
		parent::__construct($identifier, $label, $isSortable, $sortAscendingByDefault, $columnScope);
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		return call_user_func($this->callbackFunction, $tableItemModel);
	}
}