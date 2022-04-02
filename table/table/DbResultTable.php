<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\table;

use framework\core\HttpRequest;
use framework\datacheck\Sanitizer;
use framework\db\FrameworkDB;
use framework\table\column\AbstractTableColumn;
use framework\table\filter\AbstractTableFilter;
use framework\table\renderer\SortableTableHeadRenderer;
use framework\table\renderer\TablePaginationRenderer;
use framework\table\TableHelper;
use framework\table\TableItemModel;

class DbResultTable extends SmartTable
{
	public const PARAM_SORT = 'sort';
	public const PARAM_RESET = 'reset';
	public const PARAM_PAGE = 'page';
	public const PARAM_FIND = 'find';

	public const sessionDataType = 'table';
	public const filter = '[filter]';
	public const pagination = '[pagination]';

	private FrameworkDB $db;
	private bool $filledDataBySelectQuery = false;
	private ?AbstractTableColumn $defaultSortColumn = null;
	private string $selectQuery;
	private array $params;
	private int $itemsPerPage;
	private ?AbstractTableFilter $abstractTableFilter;
	private TablePaginationRenderer $tablePaginationRenderer;
	private ?int $filledAmount = null;
	private ?int $totalAmount = null;
	private array $additionalLinkParameters = [];
	private bool $limitToOnePage;

	public function __construct(
		string                     $identifier, // Can be name of main table, but must be unique per site
		FrameworkDB                $db,
		string                     $selectQuery,
		array                      $params = [],
		?AbstractTableFilter       $abstractTableFilter = null,
		?TablePaginationRenderer   $tablePaginationRenderer = null,
		?SortableTableHeadRenderer $sortableTableHeadRenderer = null,
		int                        $itemsPerPage = 25, // Max rows in table before pagination starts, if result is not limited to one page
		bool                       $limitToOnePage = false // Set to true to disable pagination
	)
	{
		if (is_null($sortableTableHeadRenderer)) {
			$sortableTableHeadRenderer = new SortableTableHeadRenderer();
		}
		parent::__construct($identifier, $sortableTableHeadRenderer);

		$this->setNoDataHtml(noDataHtml: DbResultTable::filter . $this->getNoDataHtml());
		$this->setFullHtml(fullHtml: DbResultTable::filter . SmartTable::totalAmount . DbResultTable::pagination . '<div class="table-global-wrap">' . SmartTable::table . '</div>' . DbResultTable::pagination);

		$this->db = $db;
		$this->selectQuery = $selectQuery;
		$this->params = $params;
		$this->abstractTableFilter = $abstractTableFilter;
		$this->tablePaginationRenderer = is_null(value: $tablePaginationRenderer) ? new TablePaginationRenderer() : $tablePaginationRenderer;
		$this->itemsPerPage = $itemsPerPage;
		$this->limitToOnePage = $limitToOnePage;
	}

	public static function getFromSession(string $dataType, string $identifier, string $index): ?string
	{
		if (!isset($_SESSION[$dataType][$identifier])) {
			$_SESSION[$dataType][$identifier] = [];
		}

		return $_SESSION[$dataType][$identifier][$index] ?? null;
	}

	public static function saveToSession(string $dataType, string $identifier, string $index, string $value): void
	{
		$_SESSION[$dataType][$identifier][$index] = $value;
	}

	public function getCurrentSortColumn(): ?string
	{
		return DbResultTable::getFromSession(dataType: DbResultTable::sessionDataType, identifier: $this->getIdentifier(), index: 'sort_column');
	}

	public function getCurrentSortDirection(): string
	{
		return DbResultTable::getFromSession(dataType: DbResultTable::sessionDataType, identifier: $this->getIdentifier(), index: 'sort_direction');
	}

	public function getCurrentPaginationPage(): int
	{
		return (int)DbResultTable::getFromSession(dataType: DbResultTable::sessionDataType, identifier: $this->getIdentifier(), index: 'pagination_page');
	}

	public function addColumn(AbstractTableColumn $abstractTableColumn, bool $isDefaultSortColumn = false): void
	{
		parent::addColumn(abstractTableColumn: $abstractTableColumn);

		if ($isDefaultSortColumn) {
			$this->defaultSortColumn = $abstractTableColumn;
		}
	}

	public function render(): string
	{
		$this->fillBySelectQuery();

		$html = parent::render();

		return str_replace(
			[
				DbResultTable::filter,
				DbResultTable::pagination,
			],
			[
				is_null($this->abstractTableFilter) ? '' : $this->abstractTableFilter->render(),
				$this->tablePaginationRenderer->render(dbResultTable: $this, entriesPerPage: $this->itemsPerPage),
			],
			$html
		);
	}

	public function fillBySelectQuery(): void
	{
		if ($this->filledDataBySelectQuery) {
			return;
		}

		if (!is_null($this->abstractTableFilter)) {
			$this->abstractTableFilter->validate(dbResultTable: $this);
		}
		$this->initSorting();
		$this->initPaginationPage();

		$orderBySql = '';
		$sortColumn = $this->getCurrentSortColumn();
		$sortDirection = $this->getCurrentSortDirection();
		if (!empty($sortColumn)) {
			$orderBySql = ' ORDER BY ' . $sortColumn . (($sortDirection === TableHelper::SORT_DESC) ? ' ' . TableHelper::SORT_DESC : '');
		}

		$sql = implode(separator: PHP_EOL, array: [
			$this->selectQuery,
			$orderBySql,
			'LIMIT ?, ?',
		]);
		$params = $this->params;
		$params[] = ($this->getCurrentPaginationPage() - 1) * $this->itemsPerPage;
		$params[] = $this->itemsPerPage;

		$res = $this->db->select(sql: $sql, parameters: $params);
		foreach ($res as $dataItem) {
			$this->addDataItem(tableItemModel: new TableItemModel($dataItem));
		}
		$this->filledDataBySelectQuery = true;
		$this->filledAmount = count($res);
	}

	private function initSorting(): void
	{
		$availableSortOptions = [];
		foreach ($this->getColumns() as $abstractTableColumn) {
			if ($abstractTableColumn->isSortable()) {
				$availableSortOptions[] = $abstractTableColumn->getIdentifier();
			}
		}

		$requestedSorting = Sanitizer::trimmedString(HttpRequest::getInputString(DbResultTable::PARAM_SORT));
		if ($requestedSorting !== '') {
			$requestedSortingArr = explode(separator: '|', string: $requestedSorting);
			if (count(value: $requestedSortingArr) === 3) {
				$requestedSortTable = $requestedSortingArr[0];
				$requestedSortColumn = $requestedSortingArr[1];
				$requestedSortDirection = $requestedSortingArr[2];

				if (
					$requestedSortTable === $this->getIdentifier()
					&& in_array(needle: $requestedSortColumn, haystack: $availableSortOptions)
					&& array_key_exists(key: $requestedSortDirection, array: TableHelper::OPPOSITE_SORT_DIRECTION)
				) {
					DbResultTable::saveToSession(dataType: DbResultTable::sessionDataType, identifier: $this->getIdentifier(), index: 'sort_column', value: $requestedSortColumn);
					DbResultTable::saveToSession(dataType: DbResultTable::sessionDataType, identifier: $this->getIdentifier(), index: 'sort_direction', value: $requestedSortDirection);
				}
			}
		}

		if (empty($this->getCurrentSortColumn()) || !is_null(HttpRequest::getInputString(keyName: DbResultTable::PARAM_RESET))) {
			$defaultSortColumn = $this->defaultSortColumn;
			if (is_null($defaultSortColumn)) {
				DbResultTable::saveToSession(dataType: DbResultTable::sessionDataType, identifier: $this->getIdentifier(), index: 'sort_column', value: current(array: $this->getColumns())->getIdentifier());
				DbResultTable::saveToSession(dataType: DbResultTable::sessionDataType, identifier: $this->getIdentifier(), index: 'sort_direction', value: TableHelper::SORT_ASC);
			} else {
				DbResultTable::saveToSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'sort_column', $defaultSortColumn->getIdentifier());
				DbResultTable::saveToSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'sort_direction', $defaultSortColumn->isSortAscendingByDefault() ? TableHelper::SORT_ASC : TableHelper::SORT_DESC);
			}
		}
	}

	private function initPaginationPage(): void
	{
		$inputPageArr = explode(separator: '|', string: Sanitizer::trimmedString(HttpRequest::getInputString(keyName: DbResultTable::PARAM_PAGE)));
		$inputPage = (int)$inputPageArr[0];
		$inputTable = Sanitizer::trimmedString($inputPageArr[1] ?? '');
		if ($inputTable === $this->getIdentifier() && $inputPage > 0) {
			DbResultTable::saveToSession(dataType: DbResultTable::sessionDataType, identifier: $this->getIdentifier(), index: 'pagination_page', value: $inputPage);
		}

		if (
			$this->getCurrentPaginationPage() < 1
			|| !is_null(HttpRequest::getInputString(keyName: DbResultTable::PARAM_FIND))
			|| !is_null(HttpRequest::getInputString(keyName: DbResultTable::PARAM_RESET))
		) {
			DbResultTable::saveToSession(dataType: DbResultTable::sessionDataType, identifier: $this->getIdentifier(), index: 'pagination_page', value: 1);
		}
	}

	public function getTotalAmount(): int
	{
		if (!is_null(value: $this->totalAmount)) {
			return $this->totalAmount;
		}

		$this->fillBySelectQuery();

		if (($this->getCurrentPaginationPage() === 1 && $this->filledAmount < $this->itemsPerPage) || $this->limitToOnePage) {
			return $this->totalAmount = $this->filledAmount;
		}

		$minifiedQuery = Sanitizer::trimmedString(input: preg_replace(pattern: '#\s+#', replacement: ' ', subject: $this->selectQuery));
		$allQueryParts = explode(separator: ' ', string: $minifiedQuery);

		$newCountQueryParts = ['SELECT', 'COUNT(*) AS amount'];

		$amountOfSelects = 0;
		$addToNewCountQueryParts = false;
		foreach ($allQueryParts as $queryPart) {
			if ($addToNewCountQueryParts) {
				$newCountQueryParts[] = $queryPart;
				continue;
			}

			$uppercaseQueryPart = str_replace(search: ['(', ')'], replace: '', subject: strtoupper(string: Sanitizer::trimmedString(input: $queryPart)));
			if ($uppercaseQueryPart === 'SELECT') {
				$amountOfSelects++;
				continue;
			}

			if ($uppercaseQueryPart === 'FROM') {
				if ($amountOfSelects > 1) {
					$amountOfSelects--;
					continue;
				}
				$addToNewCountQueryParts = true;
				$newCountQueryParts[] = $uppercaseQueryPart;
			}
		}

		$countQuery = implode(separator: ' ', array: $newCountQueryParts);
		$amountOfCountParameters = count(value: explode(separator: '?', string: $countQuery)) - 1;
		$parametersToRemoveForCount = count(value: $this->params) - $amountOfCountParameters;
		$countParameters = [];
		$i = 0;
		foreach ($this->params as $parameter) {
			$i++;
			if ($i <= $parametersToRemoveForCount) {
				continue;
			}
			$countParameters[] = $parameter;
		}

		return $this->totalAmount = $this->db->select(sql: $countQuery, parameters: $countParameters)[0]->amount;
	}

	public function getDb(): FrameworkDB
	{
		return $this->db;
	}

	public function getSelectQuery(): string
	{
		return $this->selectQuery;
	}

	public function setParams(array $params): void
	{
		$this->params = $params;
	}

	public function addToSelectQuery(string $additionalSql): void
	{
		$this->selectQuery .= PHP_EOL . $additionalSql;
	}

	public function addParam(string|float|int $param): void
	{
		$this->params[] = $param;
	}

	public function addAdditionalLinkParameter(string $key, string $value): void
	{
		$this->additionalLinkParameters[urlencode($key)] = urlencode(string: $value);
	}

	public function getAdditionalLinkParameters(): array
	{
		return $this->additionalLinkParameters;
	}

	public function setLimitToOnePage(bool $limitToOnePage): void
	{
		$this->limitToOnePage = $limitToOnePage;
	}
}