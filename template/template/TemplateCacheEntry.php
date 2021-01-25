<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\template;

class TemplateCacheEntry
{
	private string $path;
	private int $changeTime;
	private int $size;

	public function __construct(string $path, int $changeTime, int $size)
	{
		$this->path = $path;
		$this->changeTime = $changeTime;
		$this->size = $size;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getChangeTime(): int
	{
		return $this->changeTime;
	}

	public function getSize(): int
	{
		return $this->size;
	}
}