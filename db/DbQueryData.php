<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\db;

class DbQueryData
{
	private string $query;
	private array $params;

	public function __construct(string $query, array $params)
	{
		$this->query = $query;
		$this->params = $params;
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