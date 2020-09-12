<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\table\table;

use framework\table\TableItemModel;
use LogicException;
use framework\table\column\AbstractTableColumn;
use framework\table\column\DefaultColumn;
use framework\table\TableHelper;

class Table
{
	/** @var Table[] */
	private static array $instances = [];

	private string $identifier;
	/** @var TableItemModel[] */
	private array $items = [];
	private array $cssClasses = [];
	/** @var AbstractTableColumn[] */
	private array $columns = [];
	private string $msg_noResults = 'Es wurden keine Einträge gefunden.';
	private string $msg_oneResult = 'Es wurde <strong>1</strong> Resultat gefunden.';
	private string $msg_numResults = 'Es wurden <strong>[COUNT]</strong> Resultate gefunden.';
	private string $tableWrapperCode = '<div class="table-global-wrap">[TABLE]</div>';

	public function __construct(string $identifier)
	{
		if (array_key_exists($identifier, self::$instances)) {
			throw new LogicException('There is already a table with the same identifier ' . $identifier);
		}

		$this->identifier = $identifier;

		self::$instances[$this->identifier] = $this;
	}

	public function setMsgNoResults(string $msg_noResults): void
	{
		$this->msg_noResults = $msg_noResults;
	}

	public function setMsgOneResult(string $msg_oneResult): void
	{
		$this->msg_oneResult = $msg_oneResult;
	}

	public function setMsgNumResults(string $msg_numResults): void
	{
		$this->msg_numResults = $msg_numResults;
	}

	public function setTableWrapperCode(string $tableWrapperCode): void
	{
		$this->tableWrapperCode = $tableWrapperCode;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getFromSession(string $index): ?string
	{
		$sessionIndex = 'table_' . $this->getIdentifier();

		if (!isset($_SESSION[$sessionIndex])) {
			$_SESSION[$sessionIndex] = [];
		}

		return $_SESSION[$sessionIndex][$index] ?? null;
	}

	public function saveToSession(string $index, string $value): void
	{
		$sessionIndex = 'table_' . $this->getIdentifier();
		$_SESSION[$sessionIndex][$index] = $value;
	}

	public function addCssClass(string $className): void
	{
		$this->cssClasses[] = $className;
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

	public function addTableItem(TableItemModel $tableItemModel): void
	{
		$this->items[] = $tableItemModel;
	}

	/**
	 * @return TableItemModel[]
	 */
	public function getItems(): array
	{
		return $this->items;
	}

	public function getTotalAmount(): int
	{
		return count($this->items);
	}

	public function render(bool $renderTotalAmount, bool $renderRawData = false): string
	{
		if (count($this->items) === 0) {
			return '<p class="no-entry">' . $this->msg_noResults . '</p>';
		}

		$totalAmountHtml = '';
		if ($renderTotalAmount) {
			$totalAmount = $this->getTotalAmount();
			$totalAmountHtml = implode('', [
				'<p class="search-result">',
				(($totalAmount > 1) ? str_replace('[COUNT]', number_format($totalAmount, 0, '.', '\''), $this->msg_numResults) : $this->msg_oneResult),
				'</p>',
			]);
		}

		$tableAttributes = ['table'];
		if (count($this->cssClasses) > 0) {
			$tableAttributes[] = 'class="' . implode(' ', $this->cssClasses) . '"';
		}

		$rowNumber = 0;

		$headArr = [];
		$bodyArr = [];
		$currentSortColumn = $this->getFromSession('sort_column');
		$currentSortDirection = $this->getFromSession('sort_direction');

		foreach ($this->items as $item) {
			$rowNumber++;

			$bodyArr[] = '<tr' . (($rowNumber % 2) !== 0 ? ' class="odd"' : '') . '>';
			if ($renderRawData) {
				foreach ($item->getAllData() as $index => $val) {
					$defaultColumn = new DefaultColumn($index, $index, false);
					if ($rowNumber === 1) {
						if ($currentSortColumn === $index) {
							$columnSortDirection = TableHelper::OPPOSITE_SORT_DIRECTION[$currentSortDirection];
						} else {
							$columnSortDirection = $defaultColumn->isSortAscendingByDefault() ? TableHelper::SORT_ASC : TableHelper::SORT_DESC;
						}
						$headArr[] = $defaultColumn->renderHead($columnSortDirection);
					}

					$bodyArr[] = $defaultColumn->renderCell($item);
				}
			} else {
				foreach ($this->columns as $abstractTableColumn) {
					if ($rowNumber === 1) {
						if ($currentSortColumn === $abstractTableColumn->getIdentifier()) {
							$columnSortDirection = TableHelper::OPPOSITE_SORT_DIRECTION[$currentSortDirection];
						} else {
							$columnSortDirection = $abstractTableColumn->isSortAscendingByDefault() ? TableHelper::SORT_ASC : TableHelper::SORT_DESC;
						}
						$headArr[] = $abstractTableColumn->renderHead($columnSortDirection);
					}
					$bodyArr[] = $abstractTableColumn->renderCell($item);
				}
			}
			$bodyArr[] = '</tr>';
		}

		return implode(PHP_EOL, [
			$totalAmountHtml,
			str_replace('[TABLE]', implode(PHP_EOL, [
				'<' . implode(' ', $tableAttributes) . '>',
				'<thead>',
				'<tr>',
				implode(PHP_EOL, $headArr),
				'</tr>',
				'</thead>',
				'<tbody>',
				implode(PHP_EOL, $bodyArr),
				'</tbody>',
				'</table>',
			]), $this->tableWrapperCode),
		]);
	}
}
/* EOF */