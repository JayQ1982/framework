<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\table;

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
		if ($renderNewLines) {
			return nl2br(htmlspecialchars(str_replace('<br>', "\n", $this->data[$name]), ENT_NOQUOTES));
		}

		return htmlspecialchars($this->data[$name], ENT_NOQUOTES);
	}

	public function getAllData(): array
	{
		return $this->data;
	}
}
/* EOF */