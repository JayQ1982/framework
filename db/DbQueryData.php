<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\db;

readonly class DbQueryData
{
	public function __construct(
		public string $query,
		public array  $params
	) {
	}
}