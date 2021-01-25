<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\table;

use framework\core\HttpRequest;
use framework\db\FrameworkDB;
use framework\table\column\ActionsColumn;
use framework\table\column\CallbackColumn;
use framework\table\column\DateColumn;
use framework\table\column\DefaultColumn;
use framework\table\column\OptionsColumn;
use framework\table\filter\AbstractTableFilter;
use framework\table\renderer\SortableTableHeadRenderer;
use framework\table\renderer\TablePaginationRenderer;
use framework\table\table\DbResultTable;
use framework\table\table\SmartTable;

class TableHelper
{
	const SORT_ASC = 'ASC';
	const SORT_DESC = 'DESC';
	const OPPOSITE_SORT_DIRECTION = [
		TableHelper::SORT_ASC  => TableHelper::SORT_DESC,
		TableHelper::SORT_DESC => TableHelper::SORT_ASC,
	];

	public static function createTable(string $identifier): SmartTable
	{
		return new SmartTable($identifier);
	}

	public static function createDbResultTable(
		string $identifier,
		FrameworkDB $db,
		HttpRequest $httpRequest,
		string $selectQuery,
		array $params = [],
		?AbstractTableFilter $abstractTableFilter = null,
		?TablePaginationRenderer $tablePaginationRenderer = null,
		?SortableTableHeadRenderer $sortableTableHeadRenderer = null,
		int $itemsPerPage = 25
	): DbResultTable {
		return new DbResultTable($identifier, $db, $httpRequest, $selectQuery, $params, $abstractTableFilter, $tablePaginationRenderer, $sortableTableHeadRenderer, $itemsPerPage);
	}

	public static function createActionsColumn(string $label = ''): ActionsColumn
	{
		return new ActionsColumn($label);
	}

	public static function createDateColumn(string $identifier, string $label, bool $isSortable = false, bool $sortAscendingByDefault = true, bool $columnScope = true): DateColumn
	{
		return new DateColumn($identifier, $label, $isSortable, $sortAscendingByDefault, $columnScope);
	}

	public static function createDefaultColumn(string $identifier, string $label, bool $isSortable = false, bool $sortAscendingByDefault = true, bool $columnScope = true): DefaultColumn
	{
		return new DefaultColumn($identifier, $label, $isSortable, $sortAscendingByDefault, $columnScope);
	}

	public static function createOptionsColumn(string $identifier, string $label, array $options, bool $isOrderAble, bool $orderAscending = true, bool $columnScope = true): OptionsColumn
	{
		return new OptionsColumn($identifier, $label, $options, $isOrderAble, $orderAscending, $columnScope);
	}

	public static function createCallbackColumn(string $identifier, string $label, callable $callbackFunction, bool $isSortable = false, bool $sortAscendingByDefault = true, bool $columnScope = true): CallbackColumn
	{
		return new CallbackColumn($identifier, $label, $callbackFunction, $isSortable, $sortAscendingByDefault, $columnScope);
	}
}