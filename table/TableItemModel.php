<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table;

use framework\html\HtmlDocument;
use stdClass;

class TableItemModel
{
	private array $data;

	public function __construct(stdClass $dataObject)
	{
		$this->data = get_object_vars($dataObject);
	}

	public function getRawValue(string $name)
	{
		return $this->data[$name];
	}

	public function renderValue(string $name, bool $renderNewLines = false): string
	{
		$value = $this->data[$name];
		if (is_null($value)) {
			return '';
		}

		if ($renderNewLines) {
			return nl2br(HtmlDocument::htmlEncode(str_replace('<br>', "\n", $value), true));
		}

		return HtmlDocument::htmlEncode($value, true);
	}

	public function getAllData(): array
	{
		return $this->data;
	}
}