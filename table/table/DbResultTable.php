<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\table\table;

use framework\core\HttpRequest;
use framework\db\FrameworkDB;
use framework\common\Pagination;
use framework\table\column\AbstractTableColumn;
use framework\table\TableHelper;
use framework\table\TableItemModel;

class DbResultTable extends Table
{
	private FrameworkDB $db;
	private HttpRequest $httpRequest;
	private string $selectQuery;
	private array $params;
	private int $currentPage = 1;
	private int $itemsPerPage;
	private ?AbstractTableColumn $defaultSortColumn = null;
	private bool $filledDataBySelectQuery = false;
	private ?int $filledAmount = null;
	private ?int $totalAmount = null;

	public function __construct(
		string $identifier,
		FrameworkDB $db,
		HttpRequest $httpRequest,
		string $selectQuery,
		array $params = [],
		int $itemsPerPage = 25
	) {
		parent::__construct($identifier);

		$this->db = $db;
		$this->httpRequest = $httpRequest;
		$this->selectQuery = $selectQuery;
		$this->params = $params;
		$this->itemsPerPage = $itemsPerPage;
	}

	public function addColumn(AbstractTableColumn $abstractTableColumn, bool $isDefaultSortColumn = false): void
	{
		parent::addColumn($abstractTableColumn);

		if ($isDefaultSortColumn) {
			$this->defaultSortColumn = $abstractTableColumn;
		}
	}

	public function render(bool $renderTotalAmount, bool $renderRawData = false): string
	{
		$this->fillBySelectQuery();

		$totalAmount = $this->getTotalAmount();
		$filledAmount = $this->filledAmount;

		$paginationHtml = '';
		if ($totalAmount > $filledAmount) {
			$paginationHtml = Pagination::render($totalAmount, $this->currentPage, $this->itemsPerPage);
		}

		$returnHtml = parent::render($renderTotalAmount, $renderRawData);

		return $paginationHtml . $returnHtml . $paginationHtml;
	}

	public function fillBySelectQuery()
	{
		if ($this->filledDataBySelectQuery) {
			return;
		}
		$httpRequest = $this->httpRequest;
		$this->initSorting($httpRequest);
		$this->initPaginationPage($httpRequest);

		$orderBySql = '';
		$sortColumn = $this->getFromSession('sort_column');
		$sortDirection = $this->getFromSession('sort_direction');
		if (!empty($sortColumn)) {
			$orderBySql = ' ORDER BY ' . $sortColumn . (($sortDirection === TableHelper::SORT_DESC) ? ' ' . TableHelper::SORT_DESC : '');
		}

		$sql = implode(PHP_EOL, [
			$this->selectQuery,
			$orderBySql,
			'LIMIT ?, ?',
		]);
		$params = $this->params;
		$params[] = ($this->currentPage - 1) * $this->itemsPerPage;
		$params[] = $this->itemsPerPage;

		$res = $this->db->select($sql, $params);
		foreach ($res as $dataItem) {
			$this->addTableItem(new TableItemModel($dataItem));
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
					$this->saveToSession('sort_column', $requestedSortColumn);
					$this->saveToSession('sort_direction', $requestedSortDirection);
				}
			}
		}

		if (is_null($this->getFromSession('sort_column')) || !is_null($httpRequest->getInputString('reset'))) {
			$defaultSortColumn = $this->defaultSortColumn;
			if (is_null($defaultSortColumn)) {
				$this->saveToSession('sort_column', '');
				$this->saveToSession('sort_direction', TableHelper::SORT_ASC);
			} else {
				$this->saveToSession('sort_column', $defaultSortColumn->getIdentifier());
				$this->saveToSession('sort_direction', $defaultSortColumn->isSortAscendingByDefault() ? TableHelper::SORT_ASC : TableHelper::SORT_DESC);
			}
		}
	}

	private function initPaginationPage(HttpRequest $httpRequest): void
	{
		$requestedPage = $httpRequest->getInputInteger('page');
		if (!empty($requestedPage)) {
			$this->saveToSession('pagination_page', $requestedPage);
		}

		if (
			(int)$this->getFromSession('pagination_page') < 1
			|| !is_null($httpRequest->getInputString('find'))
			|| !is_null($httpRequest->getInputString('reset'))
		) {
			$this->saveToSession('pagination_page', 1);
		}

		$this->currentPage = (int)$this->getFromSession('pagination_page');
	}

	public function getTotalAmount(): int
	{
		if (!is_null($this->totalAmount)) {
			return $this->totalAmount;
		}

		$this->fillBySelectQuery();

		if ($this->currentPage === 1 && $this->filledAmount < $this->itemsPerPage) {
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

		return $this->totalAmount = $this->db->select(implode(' ', $newCountQueryParts), $this->params)[0]->amount;
	}
}
/* EOF */