<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table;

use framework\html\HtmlEncoder;
use stdClass;

class TableItemModel
{
	private array $data;

	public function __construct(stdClass $dataObject)
	{
		$this->data = get_object_vars(object: $dataObject);
	}

	public function getRawValue(string $name)
	{
		return $this->data[$name];
	}

	public function renderValue(string $name, bool $renderNewLines = false): string
	{
		$value = $this->data[$name];
		if (is_null(value: $value)) {
			return '';
		}

		if ($renderNewLines) {
			return nl2br(string: HtmlEncoder::encodeKeepQuotes(value: str_replace(search: '<br>', replace: PHP_EOL, subject: $value)));
		}

		return HtmlEncoder::encodeKeepQuotes(value: $value);
	}

	public function getAllData(): array
	{
		return $this->data;
	}
}