<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\common\SearchHelper;
use framework\core\HttpRequest;
use framework\datacheck\Sanitizer;
use framework\html\HtmlEncoder;

class TextFilterField extends AbstractTableFilterField
{
	private string $value = '';
	private array $sqlParams = [];

	public function __construct(
		AbstractTableFilter     $parentFilter,
		string                  $identifier,
		private readonly string $label,
		private readonly string $dataTableColumnReference
	) {
		parent::__construct(parentFilter: $parentFilter, filterFieldIdentifier: $identifier);
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
		$inputValue = HttpRequest::getInputString(keyName: $this->getIdentifier());
		$this->value = Sanitizer::trimmedString($inputValue);
		$this->saveToSession(index: $this->getIdentifier(), value: $this->value);
	}

	public function getWhereConditions(): array
	{
		$dbQueryData = SearchHelper::createSQLFilters(filterArr: [
			preg_replace(
				pattern: '!\s+!',
				replacement: ' ',
				subject: $this->dataTableColumnReference
			) => $this->value,
		]);
		$query = $dbQueryData->query;
		if (Sanitizer::trimmedString(input: $query) === '') {
			return [];
		}
		foreach ($dbQueryData->params as $param) {
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
		return $this->label . '<input type="text" class="text" name="' . $this->getIdentifier() . '" id="filter-' . $this->getIdentifier() . '" value="' . HtmlEncoder::encode(value: $this->value) . '">';
	}

	protected function highLightLabel(): bool
	{
		return !empty($this->value);
	}
}