<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\model;

class FileDataModel
{
	private string $name;
	private string $tmp_name;
	private string $type;
	private int $error;
	private int $size;

	public function __construct(string $name, string $tmp_name, string $type, int $error, int $size)
	{
		$this->name = $name;
		$this->tmp_name = $tmp_name;
		$this->type = $type;
		$this->error = $error;
		$this->size = $size;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setTmpName(string $tmp_name): void
	{
		$this->tmp_name = $tmp_name;
	}

	public function getTmpName(): string
	{
		return $this->tmp_name;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getError(): int
	{
		return $this->error;
	}

	public function getSize(): int
	{
		return $this->size;
	}
}