<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\autoloader;

class AutoloaderPathModel
{
	const MODE_UNDERSCORE = 'underscore';
	const MODE_NAMESPACE = 'namespace';

	private string $name;
	private string $path;
	private string $mode;
	/** @var string[] */
	private array $fileSuffixList;
	private ?string $phpFilePathRemove;

	public function __construct(string $name, string $path, string $mode, array $classSuffixList, ?string $phpFilePathRemove = null)
	{
		$this->name = $name;
		$this->path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		$this->mode = str_replace('/', DIRECTORY_SEPARATOR, $mode);
		$this->fileSuffixList = $classSuffixList;
		$this->phpFilePathRemove = $phpFilePathRemove;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getMode(): string
	{
		return $this->mode;
	}

	public function getFileSuffixList(): array
	{
		return $this->fileSuffixList;
	}

	public function getPhpFilePathRemove(): ?string
	{
		return $this->phpFilePathRemove;
	}
}
/* EOF */