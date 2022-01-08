<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\security\CsrfToken;
use framework\table\table\DbResultTable;

class TableFilter extends AbstractTableFilter
{
	/** @var AbstractTableFilterField[] */
	private array $filterFields = [];

	public function addField(AbstractTableFilterField $abstractTableFilterField)
	{
		$abstractTableFilterField->init();
		$this->filterFields[$abstractTableFilterField->getIdentifier()] = $abstractTableFilterField;
	}

	protected function reset(): void
	{
		foreach ($this->filterFields as $abstractTableFilterField) {
			$abstractTableFilterField->reset();
		}
	}

	protected function checkInput(DbResultTable $dbResultTable): void
	{
		foreach ($this->filterFields as $abstractTableFilterField) {
			$abstractTableFilterField->checkInput();
		}
	}

	protected function applyFilters(DbResultTable $dbResultTable): bool
	{
		$whereConds = [];
		$params = [];

		foreach ($this->filterFields as $abstractTableFilterField) {
			foreach ($abstractTableFilterField->getWhereConditions() as $whereCondition) {
				$whereConds[] = $whereCondition;
			}

			foreach ($abstractTableFilterField->getSqlParameters() as $sqlParameter) {
				$params[] = $sqlParameter;
			}
		}

		if (count($whereConds) === 0) {
			return false;
		}

		$this->addWhereConditionsToSelectQuery(
			dbResultTable: $dbResultTable,
			whereConds: $whereConds,
			params: $params
		);

		return true;
	}

	private function addWhereConditionsToSelectQuery(DbResultTable $dbResultTable, array $whereConds, array $params): void
	{
		foreach ($whereConds as $key => $val) {
			$whereConds[$key] = '(' . $val . ')';
		}

		$currentSelectQuery = $dbResultTable->getSelectQuery();
		if ((int)strrpos(haystack: $currentSelectQuery, needle: 'WHERE') < strrpos(haystack: $currentSelectQuery, needle: 'FROM')) {
			$dbResultTable->addToSelectQuery(additionalSql: 'WHERE ' . implode(separator: ' AND ', array: $whereConds));
		} else {
			$dbResultTable->addToSelectQuery(additionalSql: 'AND ' . implode(separator: ' AND ', array: $whereConds));
		}

		foreach ($params as $param) {
			$dbResultTable->addParam(param: $param);
		}
	}

	public function render(): string
	{
		$htmlArr = [
			'<div class="table-filter-wrapper clearfix">',
			'<div class="table-filter-legend-wrap clearfix">',
			'<a href="#" class="trigger-table-filter-legend">Suchoptionen</a>',
			'<div class="table-filter-legend clearfix">',
			'<dl class="clearfix"><dt>.</dt><dd>Feld ist nicht leer</dd></dl>',
			'<dl class="clearfix"><dt>!term</dt><dd>Feld enthält "term" nicht</dd></dl>',
			'<dl class="clearfix"><dt>_</dt><dd>Feld ist leer</dd></dl>',
			'<dl class="clearfix"><dt>%</dt><dd>Wildcard</dd></dl>',
			'<dl class="clearfix"><dt>*</dt><dd>Wildcard</dd></dl>',
			'</div>',
			'</div>',
			'<form method="post" action="?find=' . $this->getIdentifier() . '" class="form-tablefilter">',
			CsrfToken::renderAsHiddenPostField(),
		];

		$htmlArr[] = '<div class="table-filter-primary-wrap">';
		$htmlArr[] = '<ul class="table-filter table-filter-primary clearfix">';
		foreach ($this->filterFields as $abstractTableFilterField) {
			$htmlArr[] = $abstractTableFilterField->render();
		}
		$htmlArr[] = '</ul>';
		$htmlArr[] = '</div>';
		$htmlArr[] = '<div class="filter-submit">';
		$htmlArr[] = '<button type="submit" value="" class="btn btn-primary">Filter anwenden</button>';
		$htmlArr[] = '<a class="reset-filter" href="?reset">Filter zurücksetzen</a>';
		$htmlArr[] = '</div>';
		$htmlArr[] = '</form>';
		$htmlArr[] = '</div>';

		return implode(separator: PHP_EOL, array: $htmlArr);
	}
}