<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table;

use framework\db\FrameworkDB;
use framework\table\column\ActionsColumn;
use framework\table\column\CallbackColumn;
use framework\table\column\DateColumn;
use framework\table\column\DefaultColumn;
use framework\table\column\OptionsColumn;
use framework\table\filter\AbstractTableFilter;
use framework\table\renderer\SortableTableHeadRenderer;
use framework\table\renderer\TableHeadRenderer;
use framework\table\renderer\TablePaginationRenderer;
use framework\table\table\DbResultTable;
use framework\table\table\SmartTable;

class TableHelper
{
	public const SORT_ASC = 'ASC';
	public const SORT_DESC = 'DESC';
	public const OPPOSITE_SORT_DIRECTION = [
		TableHelper::SORT_ASC  => TableHelper::SORT_DESC,
		TableHelper::SORT_DESC => TableHelper::SORT_ASC,
	];

	public static function createTable(string $identifier, ?TableHeadRenderer $tableHeadRenderer = null): SmartTable
	{
		return new SmartTable(identifier: $identifier, tableHeadRenderer: $tableHeadRenderer);
	}

	public static function createDbResultTable(
		string                     $identifier,
		FrameworkDB                $db,
		string                     $selectQuery,
		array                      $params = [],
		?AbstractTableFilter       $abstractTableFilter = null,
		?TablePaginationRenderer   $tablePaginationRenderer = null,
		?SortableTableHeadRenderer $sortableTableHeadRenderer = null,
		int                        $itemsPerPage = 25
	): DbResultTable {
		return new DbResultTable(
			identifier: $identifier,
			db: $db,
			selectQuery: $selectQuery,
			params: $params,
			abstractTableFilter: $abstractTableFilter,
			tablePaginationRenderer: $tablePaginationRenderer,
			sortableTableHeadRenderer: $sortableTableHeadRenderer,
			itemsPerPage: $itemsPerPage
		);
	}

	public static function createActionsColumn(string $label = '', string $cellCssClass = 'action'): ActionsColumn
	{
		return new ActionsColumn(label: $label, cellCssClass: $cellCssClass);
	}

	public static function createDateColumn(string $identifier, string $label, bool $isSortable = false, bool $sortAscendingByDefault = true): DateColumn
	{
		return new DateColumn(
			identifier: $identifier,
			label: $label,
			isSortable: $isSortable,
			sortAscendingByDefault: $sortAscendingByDefault
		);
	}

	public static function createDefaultColumn(string $identifier, string $label, bool $isSortable = false, bool $sortAscendingByDefault = true): DefaultColumn
	{
		return new DefaultColumn(
			identifier: $identifier,
			label: $label,
			isSortable: $isSortable,
			sortAscendingByDefault: $sortAscendingByDefault
		);
	}

	public static function createOptionsColumn(string $identifier, string $label, array $options, bool $isOrderAble, bool $orderAscending = true): OptionsColumn
	{
		return new OptionsColumn(
			identifier: $identifier,
			label: $label,
			options: $options,
			isOrderAble: $isOrderAble,
			orderAscending: $orderAscending
		);
	}

	public static function createCallbackColumn(string $identifier, string $label, callable $callbackFunction, bool $isSortable = false, bool $sortAscendingByDefault = true): CallbackColumn
	{
		return new CallbackColumn(
			identifier: $identifier,
			label: $label,
			callbackFunction: $callbackFunction,
			isSortable: $isSortable,
			sortAscendingByDefault: $sortAscendingByDefault
		);
	}
}