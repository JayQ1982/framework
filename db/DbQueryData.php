<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\db;

class DbQueryData
{
	public function __construct(
		private readonly string $query,
		private readonly array $params
	) {
	}

	public function getQuery(): string
	{
		return $this->query;
	}

	public function getParams(): array
	{
		return $this->params;
	}
}