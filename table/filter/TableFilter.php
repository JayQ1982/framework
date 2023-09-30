<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\Core;
use framework\core\HttpRequest;
use framework\html\HtmlDataObjectCollection;
use framework\html\HtmlReplacementCollection;
use framework\html\HtmlSnippet;
use framework\security\CsrfToken;
use framework\table\table\DbResultTable;
use LogicException;

class TableFilter
{
	private const sessionDataType = 'tableFilter';
	/** @var TableFilter[] */
	private static array $instances = [];

	private bool $filtersApplied = false;
	/** @var AbstractTableFilterField[] $allFilterFields */
	private array $allFilterFields = [];
	/** @var AbstractTableFilterField[] $primaryFields */
	private array $primaryFields = [];
	/** @var AbstractTableFilterField[] $secondaryFields */
	private array $secondaryFields = [];

	public function __construct(
		public readonly string   $identifier,
		private readonly bool    $showLegend = true,
		private readonly string  $resetParameter = 'reset',
		private readonly ?string $individualHtmlSnippetPath = null
	) {
		if (array_key_exists(key: $identifier, array: TableFilter::$instances)) {
			throw new LogicException(message: 'There is already a filter with the same identifier ' . $identifier);
		}
		TableFilter::$instances[$identifier] = $this;
	}

	protected function getFromSession(string $index): ?string
	{
		return DbResultTable::getFromSession(
			dataType: TableFilter::sessionDataType,
			identifier: $this->identifier,
			index: $index
		);
	}

	protected function saveToSession(string $index, string $value): void
	{
		DbResultTable::saveToSession(
			dataType: TableFilter::sessionDataType,
			identifier: $this->identifier,
			index: $index,
			value: $value
		);
	}

	public function validate(DbResultTable $dbResultTable): void
	{
		if (!is_null(value: HttpRequest::getInputString(keyName: $this->resetParameter))) {
			$this->reset();
		}
		if (!is_null(value: HttpRequest::getInputString(keyName: $this->identifier))) {
			$this->reset();
			$this->checkInput();
		}
		$this->filtersApplied = $this->applyFilters(dbResultTable: $dbResultTable);
	}

	public function isFiltersApplied(): bool
	{
		return $this->filtersApplied;
	}

	public function addPrimaryField(AbstractTableFilterField $abstractTableFilterField): void
	{
		$abstractTableFilterField->init();
		$this->primaryFields[] = $abstractTableFilterField;
		$this->allFilterFields[$abstractTableFilterField->identifier] = $abstractTableFilterField;
	}

	public function addSecondaryField(AbstractTableFilterField $abstractTableFilterField): void
	{
		$abstractTableFilterField->init();
		$this->secondaryFields[] = $abstractTableFilterField;
		$this->allFilterFields[$abstractTableFilterField->identifier] = $abstractTableFilterField;
	}

	protected function reset(): void
	{
		foreach ($this->allFilterFields as $abstractTableFilterField) {
			$abstractTableFilterField->reset();
		}
	}

	protected function checkInput(): void
	{
		foreach ($this->allFilterFields as $abstractTableFilterField) {
			$abstractTableFilterField->checkInput();
		}
	}

	/**
	 * @return AbstractTableFilterField[]
	 */
	protected function getAllFilterFields(): array
	{
		return $this->allFilterFields;
	}

	protected function applyFilters(DbResultTable $dbResultTable): bool
	{
		$whereConds = [];
		$params = [];

		foreach ($this->allFilterFields as $abstractTableFilterField) {
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

		$dbResultTable->dbQuery->addWherePart(
			wherePart: implode(separator: ' AND ', array: $whereConds),
			parameters: $params
		);
	}

	public function render(): string
	{
		$individualHtmlSnippetPath = $this->individualHtmlSnippetPath;
		$replacements = new HtmlReplacementCollection();
		$replacements->addBool(identifier: 'showLegend', booleanValue: $this->showLegend);
		$replacements->addEncodedText(
			identifier: 'formAction',
			content: '?' . $this->identifier . '&' . DbResultTable::PARAM_FIND
		);
		$replacements->addEncodedText(identifier: 'csrfField', content: CsrfToken::renderAsHiddenPostField());
		$primaryFields = new HtmlDataObjectCollection();
		foreach ($this->primaryFields as $abstractTableFilterField) {
			$primaryFields->add(htmlDataObject: $abstractTableFilterField->render());
		}
		$replacements->addHtmlDataObjectCollection(identifier: 'primaryFields', htmlDataObjectCollection: $primaryFields);
		if (count(value: $this->secondaryFields) > 0) {
			$secondaryFields = new HtmlDataObjectCollection();
			$isSecondaryFilterTriggered = false;
			foreach ($this->secondaryFields as $abstractTableFilterField) {
				$secondaryFields->add(htmlDataObject: $abstractTableFilterField->render());
				if ($abstractTableFilterField->isSelected()) {
					$isSecondaryFilterTriggered = true;
				}
			}
			$replacements->addBool(identifier: 'hasSecondaryFilters', booleanValue: true);
			$replacements->addBool(identifier: 'isSecondaryFilterTriggered', booleanValue: $isSecondaryFilterTriggered);
			$replacements->addHtmlDataObjectCollection(identifier: 'secondaryFields', htmlDataObjectCollection: $secondaryFields);
		} else {
			$replacements->addBool(identifier: 'hasSecondaryFilters', booleanValue: false);
		}
		$replacements->addEncodedText(identifier: 'resetHref', content: '?' . $this->resetParameter);

		return (new HtmlSnippet(
			htmlSnippetFilePath: is_null(value: $individualHtmlSnippetPath) ? Core::get()->frameworkDirectory . 'table' . DIRECTORY_SEPARATOR . 'filter' . DIRECTORY_SEPARATOR . 'tableFilter.html' : $individualHtmlSnippetPath,
			replacements: $replacements,
		))->render();
	}
}