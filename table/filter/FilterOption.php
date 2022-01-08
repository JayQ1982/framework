<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

class FilterOption
{
	private string $identifier;
	private string $label;
	private string $sqlCondition;
	private array $sqlParams;

	public function __construct(string $identifier, string $label, string $sqlCondition, array $sqlParams)
	{
		$this->identifier = $identifier;
		$this->label = $label;
		$this->sqlCondition = $sqlCondition;
		$this->sqlParams = $sqlParams;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function getSqlCondition(): string
	{
		return $this->sqlCondition;
	}

	public function getSqlParams(): array
	{
		return $this->sqlParams;
	}
}