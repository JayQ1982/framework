<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

readonly class FilterOption
{
	public function __construct(
		public string $identifier,
		public string $label,
		public string $sqlCondition,
		public array  $sqlParams
	) {
	}
}