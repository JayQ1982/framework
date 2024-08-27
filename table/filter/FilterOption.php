<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use framework\db\DbQueryData;

readonly class FilterOption
{
	public function __construct(
		public string      $identifier,
		public string      $label,
		public DbQueryData $whereCondition
	) {
	}

	public function render(string $selectedValue): string
	{
		$attributes = [
			'option',
			'value="' . $this->identifier . '"',
		];
		if ($this->identifier === $selectedValue) {
			$attributes[] = 'selected';
		}

		return '<' . implode(separator: ' ', array: $attributes) . '>' . $this->label . '</option>';
	}
}