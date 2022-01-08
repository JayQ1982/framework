<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\common\SearchHelper;
use framework\core\HttpRequest;
use framework\datacheck\Sanitizer;
use framework\html\HtmlDocument;

class TextFilterField extends AbstractTableFilterField
{
	private string $label;
	private string $value = '';
	private string $dataTableColumnReference;
	private array $sqlParams = [];

	public function __construct(string $identifier, string $label, string $dataTableColumnReference)
	{
		parent::__construct(identifier: $identifier);
		$this->label = $label;
		$this->dataTableColumnReference = $dataTableColumnReference;
	}

	public function init(): void
	{
		$this->value = Sanitizer::trimmedString(input: $this->getFromSession(index: $this->getIdentifier()));
	}

	public function reset(): void
	{
		$this->value = '';
		$this->saveToSession(index: $this->getIdentifier(), value: '');
	}

	public function checkInput(): void
	{
		$inputValue = HttpRequest::getInstance()->getInputString(keyName: $this->getIdentifier());
		$this->value = Sanitizer::trimmedString($inputValue);
		$this->saveToSession(index: $this->getIdentifier(), value: $this->value);
	}

	public function getWhereConditions(): array
	{
		$dbQueryData = SearchHelper::createSQLFilters(filterArr: [$this->dataTableColumnReference => $this->value]);
		$query = $dbQueryData->getQuery();
		if (Sanitizer::trimmedString(input: $query) === '') {
			return [];
		}
		foreach ($dbQueryData->getParams() as $param) {
			$this->sqlParams[] = $param;
		}

		return explode(separator: ' AND ', string: $query);
	}

	public function getSqlParameters(): array
	{
		return $this->sqlParams;
	}

	protected function renderField(): string
	{
		return $this->label . '<input type="text" class="text" name="' . $this->getIdentifier() . '" id="filter-' . $this->getIdentifier() . '" value="' . HtmlDocument::htmlEncode(value: $this->value) . '">';
	}

	protected function highLightLabel(): bool
	{
		return !empty($this->value);
	}
}