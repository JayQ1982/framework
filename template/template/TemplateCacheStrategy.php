<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\template;

use Exception;

abstract class TemplateCacheStrategy
{
	protected bool $saveOnDestruct;
	protected string $cachePath;

	public function __construct(string $cachePath)
	{
		if (file_exists($cachePath) === false) {
			throw new Exception('Cache path does not exist: ' . $cachePath);
		}

		$this->cachePath = $cachePath;

		$this->saveOnDestruct = true;
	}

	public abstract function getCachedTplFile(string $tplFile): ?TemplateCacheEntry;

	public abstract function addCachedTplFile(string $tplFile, ?TemplateCacheEntry $currentCacheEntry, string $compiledTemplateContent): TemplateCacheEntry;

	public function getCachePath()
	{
		return $this->cachePath;
	}

	public function setSaveOnDestruct(bool $saveOnDestruct)
	{
		$this->saveOnDestruct = $saveOnDestruct;
	}
}
/* EOF */