<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\table\table;

use framework\core\HttpRequest;
use framework\db\FrameworkDB;
use framework\table\column\AbstractTableColumn;
use framework\table\filter\AbstractTableFilter;
use framework\table\renderer\SortableTableHeadRenderer;
use framework\table\renderer\TablePaginationRenderer;
use framework\table\TableHelper;
use framework\table\TableItemModel;

class DbResultTable extends SmartTable
{
	public const sessionDataType = 'table';
	public const filter = '[filter]';
	public const pagination = '[pagination]';

	private FrameworkDB $db;
	private HttpRequest $httpRequest;
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
		string $identifier, // Can be name of main table, but must be unique per site
		FrameworkDB $db,
		HttpRequest $httpRequest,
		string $selectQuery,
		array $params = [],
		?AbstractTableFilter $abstractTableFilter = null,
		?TablePaginationRenderer $tablePaginationRenderer = null,
		?SortableTableHeadRenderer $sortableTableHeadRenderer = null,
		int $itemsPerPage = 25, // Max rows in table before pagination starts, if result is not limited to one page
		bool $limitToOnePage = false // Set to true to disable pagination
	)
	{
		if (is_null($sortableTableHeadRenderer)) {
			$sortableTableHeadRenderer = new SortableTableHeadRenderer();
		}
		parent::__construct($identifier, $sortableTableHeadRenderer);

		$this->setNoDataHtml(DbResultTable::filter . $this->getNoDataHtml());
		$this->setFullHtml(DbResultTable::filter . SmartTable::totalAmount . DbResultTable::pagination . '<div class="table-global-wrap">' . SmartTable::table . '</div>' . DbResultTable::pagination);

		$this->db = $db;
		$this->httpRequest = $httpRequest;
		$this->selectQuery = $selectQuery;
		$this->params = $params;
		$this->abstractTableFilter = $abstractTableFilter;
		$this->tablePaginationRenderer = is_null($tablePaginationRenderer) ? new TablePaginationRenderer() : $tablePaginationRenderer;
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
		return DbResultTable::getFromSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'sort_column');
	}

	public function getCurrentSortDirection(): string
	{
		return DbResultTable::getFromSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'sort_direction');
	}

	public function getCurrentPaginationPage(): int
	{
		return (int)DbResultTable::getFromSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'pagination_page');
	}

	public function addColumn(AbstractTableColumn $abstractTableColumn, bool $isDefaultSortColumn = false): void
	{
		parent::addColumn($abstractTableColumn);

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
				$this->tablePaginationRenderer->render($this, $this->itemsPerPage),
			],
			$html
		);
	}

	public function fillBySelectQuery(): void
	{
		if ($this->filledDataBySelectQuery) {
			return;
		}

		$httpRequest = $this->httpRequest;
		if (!is_null($this->abstractTableFilter)) {
			$this->abstractTableFilter->validate($this);
		}
		$this->initSorting($httpRequest);
		$this->initPaginationPage($httpRequest);

		$orderBySql = '';
		$sortColumn = $this->getCurrentSortColumn();
		$sortDirection = $this->getCurrentSortDirection();
		if (!empty($sortColumn)) {
			$orderBySql = ' ORDER BY ' . $sortColumn . (($sortDirection === TableHelper::SORT_DESC) ? ' ' . TableHelper::SORT_DESC : '');
		}

		$sql = implode(PHP_EOL, [
			$this->selectQuery,
			$orderBySql,
			'LIMIT ?, ?',
		]);
		$params = $this->params;
		$params[] = ($this->getCurrentPaginationPage() - 1) * $this->itemsPerPage;
		$params[] = $this->itemsPerPage;

		$res = $this->db->select($sql, $params);
		foreach ($res as $dataItem) {
			$this->addDataItem(new TableItemModel($dataItem));
		}
		$this->filledDataBySelectQuery = true;
		$this->filledAmount = count($res);
	}

	private function initSorting(HttpRequest $httpRequest): void
	{
		$availableSortOptions = [];
		foreach ($this->getColumns() as $abstractTableColumn) {
			if ($abstractTableColumn->isSortable()) {
				$availableSortOptions[] = $abstractTableColumn->getIdentifier();
			}
		}

		$requestedSorting = $httpRequest->getInputString('sort');
		if (!empty($requestedSorting)) {
			$requestedSortingArr = explode('|', $requestedSorting);
			if (count($requestedSortingArr) === 3) {
				$requestedSortTable = $requestedSortingArr[0];
				$requestedSortColumn = $requestedSortingArr[1];
				$requestedSortDirection = $requestedSortingArr[2];

				if (
					$requestedSortTable === $this->getIdentifier()
					&& in_array($requestedSortColumn, $availableSortOptions)
					&& array_key_exists($requestedSortDirection, TableHelper::OPPOSITE_SORT_DIRECTION)
				) {
					DbResultTable::saveToSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'sort_column', $requestedSortColumn);
					DbResultTable::saveToSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'sort_direction', $requestedSortDirection);
				}
			}
		}

		if (empty($this->getCurrentSortColumn()) || !is_null($httpRequest->getInputString('reset'))) {
			$defaultSortColumn = $this->defaultSortColumn;
			if (is_null($defaultSortColumn)) {
				DbResultTable::saveToSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'sort_column', current($this->getColumns())->getIdentifier());
				DbResultTable::saveToSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'sort_direction', TableHelper::SORT_ASC);
			} else {
				DbResultTable::saveToSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'sort_column', $defaultSortColumn->getIdentifier());
				DbResultTable::saveToSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'sort_direction', $defaultSortColumn->isSortAscendingByDefault() ? TableHelper::SORT_ASC : TableHelper::SORT_DESC);
			}
		}
	}

	private function initPaginationPage(HttpRequest $httpRequest): void
	{
		$inputPageArr = explode('|', $httpRequest->getInputString('page'));
		$inputPage = (int)$inputPageArr[0];
		$inputTable = trim($inputPageArr[1] ?? '');
		if ($inputTable === $this->getIdentifier() && $inputPage > 0) {
			DbResultTable::saveToSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'pagination_page', $inputPage);
		}

		if (
			$this->getCurrentPaginationPage() < 1
			|| !is_null($httpRequest->getInputString('find'))
			|| !is_null($httpRequest->getInputString('reset'))
		) {
			DbResultTable::saveToSession(DbResultTable::sessionDataType, $this->getIdentifier(), 'pagination_page', 1);
		}
	}

	public function getTotalAmount(): int
	{
		if (!is_null($this->totalAmount)) {
			return $this->totalAmount;
		}

		$this->fillBySelectQuery();

		if (($this->getCurrentPaginationPage() === 1 && $this->filledAmount < $this->itemsPerPage) || $this->limitToOnePage) {
			return $this->totalAmount = $this->filledAmount;
		}

		$minifiedQuery = trim(preg_replace('#\s+#', ' ', $this->selectQuery));
		$allQueryParts = explode(' ', $minifiedQuery);

		$newCountQueryParts = ['SELECT', 'COUNT(*) AS amount'];

		$amountOfSelects = 0;
		$addToNewCountQueryParts = false;
		foreach ($allQueryParts as $queryPart) {
			if ($addToNewCountQueryParts) {
				$newCountQueryParts[] = $queryPart;
				continue;
			}

			$uppercaseQueryPart = str_replace(['(', ')'], '', strtoupper(trim($queryPart)));
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

		$countQuery = implode(' ', $newCountQueryParts);
		$amountOfCountParameters = count(explode(separator: '?', string: $countQuery)) - 1;
		$parametersToRemoveForCount = count($this->params) - $amountOfCountParameters;
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

	public function getHttpRequest(): HttpRequest
	{
		return $this->httpRequest;
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
		$this->additionalLinkParameters[urlencode($key)] = urlencode($value);
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