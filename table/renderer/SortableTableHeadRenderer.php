<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\table\renderer;

use LogicException;
use framework\table\column\AbstractTableColumn;
use framework\table\table\DbResultTable;
use framework\table\table\SmartTable;
use framework\table\TableHelper;

class SortableTableHeadRenderer extends TableHeadRenderer
{
	private DbResultTable $dbResultTable;

	private string $sortableColumnClass = 'sort';
	private string $sortableColumnClassActiveAsc = 'sort sort-asc';
	private string $sortableColumnClassActiveDesc = 'sort sort-desc';

	private string $sortLinkClassActiveAsc = '';
	private string $sortLinkClassActiveDesc = '';

	private string $sortableColumnLabelAddition = '';
	private string $sortableColumnLabelAdditionActiveAsc = '';
	private string $sortableColumnLabelAdditionActiveDesc = '';

	public function setSortableColumnClass(string $sortableColumnClass): void
	{
		$this->sortableColumnClass = $sortableColumnClass;
	}

	public function setSortableColumnClassActiveAsc(string $sortableColumnClassActiveAsc): void
	{
		$this->sortableColumnClassActiveAsc = $sortableColumnClassActiveAsc;
	}

	public function setSortableColumnClassActiveDesc(string $sortableColumnClassActiveDesc): void
	{
		$this->sortableColumnClassActiveDesc = $sortableColumnClassActiveDesc;
	}

	public function setSortLinkClassActiveAsc(string $sortLinkClassActiveAsc): void
	{
		$this->sortLinkClassActiveAsc = $sortLinkClassActiveAsc;
	}

	public function setSortLinkClassActiveDesc(string $sortLinkClassActiveDesc): void
	{
		$this->sortLinkClassActiveDesc = $sortLinkClassActiveDesc;
	}

	public function setSortableColumnLabelAddition(string $sortableColumnLabelAddition): void
	{
		$this->sortableColumnLabelAddition = $sortableColumnLabelAddition;
	}

	public function setSortableColumnLabelAdditionActiveAsc(string $sortableColumnLabelAdditionActiveAsc): void
	{
		$this->sortableColumnLabelAdditionActiveAsc = $sortableColumnLabelAdditionActiveAsc;
	}

	public function setSortableColumnLabelAdditionActiveDesc(string $sortableColumnLabelAdditionActiveDesc): void
	{
		$this->sortableColumnLabelAdditionActiveDesc = $sortableColumnLabelAdditionActiveDesc;
	}

	public function render(SmartTable $smartTable): string
	{
		if (!($smartTable instanceof DbResultTable)) {
			throw new LogicException('$smartTable must be an instance of DbResultTable');
		}

		$this->dbResultTable = $smartTable;

		return parent::render($smartTable);
	}

	protected function renderColumnHead(AbstractTableColumn $abstractTableColumn): string
	{
		$columnLabel = $abstractTableColumn->getLabel();
		$columnCssClasses = [];

		if (!$abstractTableColumn->isSortable()) {
			$labelHtml = $columnLabel;
		} else {
			$dbResultTable = $this->dbResultTable;

			$isActiveSortColumn = ($dbResultTable->getCurrentSortColumn() === $abstractTableColumn->getIdentifier());

			if ($isActiveSortColumn) {
				$columnSortDirection = TableHelper::OPPOSITE_SORT_DIRECTION[$dbResultTable->getCurrentSortDirection()];
			} else {
				$columnSortDirection = $abstractTableColumn->isSortAscendingByDefault() ? TableHelper::SORT_ASC : TableHelper::SORT_DESC;
			}

			$getAttributes = [];
			foreach (array_merge([
				'sort' => implode('|', [
					$dbResultTable->getIdentifier(),
					$abstractTableColumn->getIdentifier(),
					$columnSortDirection,
				]),
			], $dbResultTable->getAdditionalLinkParameters()) as $key => $val) {
				$getAttributes[] = $key . '=' . $val;
			}

			$sortLinkAttributes = [
				'a',
				'href="?' . implode('&', $getAttributes) . '"',
			];

			if ($isActiveSortColumn) {
				$lowerCaseSortDirection = strtolower(TableHelper::OPPOSITE_SORT_DIRECTION[$columnSortDirection]);

				if (($lowerCaseSortDirection === 'asc') && !empty($this->sortLinkClassActiveAsc)) {
					$sortLinkAttributes[] = 'class="' . $this->sortLinkClassActiveAsc . '"';
				}
				if (($lowerCaseSortDirection === 'desc') && !empty($this->sortLinkClassActiveDesc)) {
					$sortLinkAttributes[] = 'class="' . $this->sortLinkClassActiveDesc . '"';
				}

				$columnCssClasses[] = ($lowerCaseSortDirection === 'asc') ? $this->sortableColumnClassActiveAsc : $this->sortableColumnClassActiveDesc;
				$labelAddition = ($lowerCaseSortDirection === 'asc') ? $this->sortableColumnLabelAdditionActiveAsc : $this->sortableColumnLabelAdditionActiveDesc;
			} else {
				$columnCssClasses[] = $this->sortableColumnClass;
				$labelAddition = $this->sortableColumnLabelAddition;
			}

			$labelHtml = '<' . implode(' ', $sortLinkAttributes) . '>' . $columnLabel . $labelAddition . '</a>';
		}

		$attributesArr = ['th'];
		if ($this->isAddColumnScopeAttribute()) {
			$attributesArr[] = 'scope="col"';
		}
		if (!empty($columnCssClasses)) {
			$attributesArr[] = 'class="' . implode(' ', $columnCssClasses) . '"';
		}

		return '<' . implode(' ', $attributesArr) . '>' . $labelHtml . '</th>';
	}
}