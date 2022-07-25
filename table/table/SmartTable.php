<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\table;

use framework\table\column\AbstractTableColumn;
use framework\table\renderer\TableHeadRenderer;
use framework\table\TableItemCollection;
use framework\table\TableItemModel;
use LogicException;

// Can be extended or used directly to render a table with data from different sources
class SmartTable
{
	public const totalAmount = '[totalAmount]';
	public const table = '[table]';
	public const tableHeader = '[tableHeader]';
	public const tableBody = '[tableBody]';
	public const cells = '[cells]';

	public const totalAmountMessagePlaceholder = '[TOTAL_AMOUNT_MESSAGE]';
	public const amount = '[AMOUNT]';

	private string $noDataHtml = '<p class="no-entry">Es wurden keine Einträge gefunden.</p>';
	private string $totalAmountHtml = '<p class="search-result">' . SmartTable::totalAmountMessagePlaceholder . '</p>';
	private string $fullHtml = SmartTable::totalAmount . '<div class="table-global-wrap">' . SmartTable::table . '</div>';
	private string $tableHtml = '<thead>' . SmartTable::tableHeader . '</thead><tbody>' . SmartTable::tableBody . '</tbody>';
	private string $oddRowHtml = '<tr>' . SmartTable::cells . '</tr>';
	private string $evenRowHtml = '<tr>' . SmartTable::cells . '</tr>';

	private string $totalAmountMessage_oneResult = 'Es wurde <strong>1</strong> Resultat gefunden.';
	private string $totalAmountMessage_numResults = 'Es wurden <strong>' . SmartTable::amount . '</strong> Resultate gefunden.';

	/** @var SmartTable[] */
	private static array $instances = [];

	private array $cssClasses = [];
	/** @var AbstractTableColumn[] */
	private array $columns = [];

	public function __construct(
		public readonly string              $identifier,
		private readonly TableHeadRenderer  $tableHeadRenderer = new TableHeadRenderer(),
		public readonly TableItemCollection $tableItemCollection = new TableItemCollection(),
	) {
		if (array_key_exists(key: $identifier, array: SmartTable::$instances)) {
			throw new LogicException(message: 'There is already a table with the same identifier ' . $identifier);
		}
		SmartTable::$instances[$this->identifier] = $this;
	}

	public function setFullHtml(string $fullHtml): void
	{
		$this->fullHtml = $fullHtml;
	}

	public function getFullHtml(): string
	{
		return $this->fullHtml;
	}

	public function setTableHtml(string $tableHtml): void
	{
		$this->tableHtml = $tableHtml;
	}

	public function setNoDataHtml(string $noDataHtml): void
	{
		$this->noDataHtml = $noDataHtml;
	}

	public function getNoDataHtml(): string
	{
		return $this->noDataHtml;
	}

	public function setTotalAmountHtml(string $totalAmountHtml): void
	{
		$this->totalAmountHtml = $totalAmountHtml;
	}

	public function setOddRowHtml(string $oddRowHtml): void
	{
		$this->oddRowHtml = $oddRowHtml;
	}

	public function setEvenRowHtml(string $evenRowHtml): void
	{
		$this->evenRowHtml = $evenRowHtml;
	}

	public function setTotalAmountMessageOneResult(string $totalAmountMessage_oneResult): void
	{
		$this->totalAmountMessage_oneResult = $totalAmountMessage_oneResult;
	}

	public function setTotalAmountMessageNumResults(string $totalAmountMessage_numResults): void
	{
		$this->totalAmountMessage_numResults = $totalAmountMessage_numResults;
	}

	public function addCssClass(string $className): void
	{
		$this->cssClasses[] = $className;
	}

	public function addColumn(AbstractTableColumn $abstractTableColumn): void
	{
		$columnIdentifier = $abstractTableColumn->identifier;
		if (array_key_exists(key: $columnIdentifier, array: $this->columns)) {
			throw new LogicException(message: 'There is already a column with the same identifier ' . $columnIdentifier);
		}

		$abstractTableColumn->setTableIdentifier(tableIdentifier: $this->identifier);
		$this->columns[$columnIdentifier] = $abstractTableColumn;
	}

	public function getColumns(): array
	{
		return $this->columns;
	}

	public function addDataItem(TableItemModel $tableItemModel): void
	{
		$this->tableItemCollection->add(tableItemModel: $tableItemModel);
	}

	public function getDisplayedAmount(): int
	{
		return $this->tableItemCollection->count();
	}

	public function getTotalAmount(): int
	{
		return $this->tableItemCollection->count();
	}

	public function render(): string
	{
		$totalAmountOfItems = $this->getTotalAmount();
		if ($totalAmountOfItems === 1) {
			$totalAmountMessage = $this->totalAmountMessage_oneResult;
		} else {
			$totalAmountMessage = str_replace(
				search: SmartTable::amount,
				replace: number_format(num: $totalAmountOfItems, thousands_separator: '\''),
				subject: $this->totalAmountMessage_numResults
			);
		}
		$bodyArr = [];
		$rowNumber = 0;
		foreach ($this->tableItemCollection->list() as $tableItemModel) {
			$rowNumber++;
			$cells = [];
			foreach ($this->columns as $abstractTableColumn) {
				$cells[] = $abstractTableColumn->renderCell(tableItemModel: $tableItemModel);
			}
			$rowHtml = (($rowNumber % 2) === 0) ? $this->evenRowHtml : $this->oddRowHtml;
			$bodyArr[] = str_replace(
				search: SmartTable::cells,
				replace: implode(separator: PHP_EOL, array: $cells),
				subject: $rowHtml
			);
		}
		$tableAttributes = ['table'];
		if (count(value: $this->cssClasses) > 0) {
			$tableAttributes[] = 'class="' . implode(separator: ' ', array: $this->cssClasses) . '"';
		}
		$tableHtml = str_replace(
			search: [
				SmartTable::tableHeader,
				SmartTable::tableBody,
			],
			replace: [
				$this->tableHeadRenderer->render(smartTable: $this),
				implode(separator: PHP_EOL, array: $bodyArr),
			],
			subject: $this->tableHtml
		);

		$placeholders = [
			SmartTable::totalAmount => str_replace(
				search: SmartTable::totalAmountMessagePlaceholder,
				replace: $totalAmountMessage,
				subject: $this->totalAmountHtml
			),
			SmartTable::table       => implode(separator: PHP_EOL, array: [
				'<' . implode(separator: ' ', array: $tableAttributes) . '>',
				$tableHtml,
				'</table>',
			]),
		];

		$srcArr = array_keys(array: $placeholders);
		$rplArr = array_values(array: $placeholders);

		return ($totalAmountOfItems === 0) ? str_replace(
			search: $srcArr,
			replace: $rplArr,
			subject: $this->noDataHtml) : str_replace(
			search: $srcArr,
			replace: $rplArr,
			subject: $this->fullHtml
		);
	}
}