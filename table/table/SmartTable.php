<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\table\table;

use LogicException;
use framework\table\column\AbstractTableColumn;
use framework\table\renderer\TableHeadRenderer;
use framework\table\TableItemModel;

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
	private string $identifier;

	private TableHeadRenderer $tableHeadRenderer;
	/** @var TableItemModel[] */
	private array $dataItems = [];
	private array $cssClasses = [];
	/** @var AbstractTableColumn[] */
	private array $columns = [];

	public function __construct(string $identifier, ?TableHeadRenderer $tableHeadRenderer = null)
	{
		if (array_key_exists($identifier, SmartTable::$instances)) {
			throw new LogicException('There is already a table with the same identifier ' . $identifier);
		}

		$this->identifier = $identifier;
		$this->tableHeadRenderer = is_null($tableHeadRenderer) ? new TableHeadRenderer() : $tableHeadRenderer;

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

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function addColumn(AbstractTableColumn $abstractTableColumn): void
	{
		$columnIdentifier = $abstractTableColumn->getIdentifier();
		if (array_key_exists($columnIdentifier, $this->columns)) {
			throw new LogicException('There is already a column with the same identifier ' . $columnIdentifier);
		}

		$abstractTableColumn->setTableIdentifier($this->identifier);
		$this->columns[$columnIdentifier] = $abstractTableColumn;
	}

	public function getColumns(): array
	{
		return $this->columns;
	}

	public function addDataItem(TableItemModel $tableItemModel): void
	{
		$this->dataItems[] = $tableItemModel;
	}

	/**
	 * @return TableItemModel[]
	 */
	public function getDataItems(): array
	{
		return $this->dataItems;
	}

	public function getDisplayedAmount(): int
	{
		return count($this->dataItems);
	}

	public function getTotalAmount(): int
	{
		return count($this->dataItems);
	}

	public function render(): string
	{
		$totalAmountOfItems = $this->getTotalAmount();
		if ($totalAmountOfItems === 1) {
			$totalAmountMessage = $this->totalAmountMessage_oneResult;
		} else {
			$totalAmountMessage = str_replace(SmartTable::amount, number_format($totalAmountOfItems, 0, '.', '\''), $this->totalAmountMessage_numResults);
		}

		$bodyArr = [];

		$rowNumber = 0;
		foreach ($this->dataItems as $tableItemModel) {
			$rowNumber++;

			$cells = [];
			foreach ($this->columns as $abstractTableColumn) {
				$cells[] = $abstractTableColumn->renderCell($tableItemModel);
			}

			$rowHtml = (($rowNumber % 2) === 0) ? $this->evenRowHtml : $this->oddRowHtml;
			$bodyArr[] = str_replace(SmartTable::cells, implode(PHP_EOL, $cells), $rowHtml);
		}

		$tableAttributes = ['table'];
		if (count($this->cssClasses) > 0) {
			$tableAttributes[] = 'class="' . implode(' ', $this->cssClasses) . '"';
		}

		$tableHtml = str_replace([
			SmartTable::tableHeader,
			SmartTable::tableBody,
		], [
			$this->tableHeadRenderer->render($this),
			implode(PHP_EOL, $bodyArr),
		], $this->tableHtml);

		$placeholders = [
			SmartTable::totalAmount => str_replace(SmartTable::totalAmountMessagePlaceholder, $totalAmountMessage, $this->totalAmountHtml),
			SmartTable::table       => implode(PHP_EOL, [
				'<' . implode(' ', $tableAttributes) . '>',
				$tableHtml,
				'</table>',
			]),
		];

		$srcArr = array_keys($placeholders);
		$rplArr = array_values($placeholders);

		return ($totalAmountOfItems === 0) ? str_replace($srcArr, $rplArr, $this->noDataHtml) : str_replace($srcArr, $rplArr, $this->fullHtml);
	}
}