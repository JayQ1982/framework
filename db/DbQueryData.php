<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
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