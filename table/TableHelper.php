<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\table;

use framework\core\HttpRequest;
use framework\db\FrameworkDB;
use framework\table\column\ActionsColumn;
use framework\table\column\DateColumn;
use framework\table\column\DefaultColumn;
use framework\table\column\OptionsColumn;
use framework\table\table\DbResultTable;
use framework\table\table\Table;

class TableHelper
{
	const SORT_ASC = 'ASC';
	const SORT_DESC = 'DESC';
	const OPPOSITE_SORT_DIRECTION = [
		self::SORT_ASC  => self::SORT_DESC,
		self::SORT_DESC => self::SORT_ASC,
	];

	public static function createTable(string $identifier): Table
	{
		return new Table($identifier);
	}

	public static function createDbResultTable(
		string $identifier,
		FrameworkDB $db,
		HttpRequest $httpRequest,
		string $selectQuery,
		array $params = [],
		int $itemsPerPage = 25
	): DbResultTable {
		return new DbResultTable($identifier, $db, $httpRequest, $selectQuery, $params, $itemsPerPage);
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
}
/* EOF */