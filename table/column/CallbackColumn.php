<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\table\column;

use framework\table\TableItemModel;

class CallbackColumn extends AbstractTableColumn
{
	/** @var callable */
	private $callbackFunction;

	public function __construct(string $identifier, string $label, callable $callbackFunction, bool $isSortable = false, bool $sortAscendingByDefault = true)
	{
		$this->callbackFunction = $callbackFunction;
		parent::__construct(
			identifier: $identifier,
			label: $label,
			isSortable: $isSortable,
			sortAscendingByDefault: $sortAscendingByDefault);
	}

	protected function renderCellValue(TableItemModel $tableItemModel): string
	{
		return call_user_func($this->callbackFunction, $tableItemModel); // TODO: Named parameters not working in PHP 8.0
	}
}