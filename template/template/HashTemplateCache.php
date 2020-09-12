<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\template;

use Exception;

class HashTemplateCache extends TemplateCacheStrategy
{
	const CACHE_SUFFIX = '.cache';
	private string $filePath;
	private array $registry;
	private bool $cacheChanged;

	public function __construct(string $cachePath, string $filePath)
	{
		$this->filePath = $filePath;
		parent::__construct($cachePath);
		$this->registry = $this->loadCacheFile();
		$this->cacheChanged = false;
	}

	private function loadCacheFile()
	{
		$cache = [];
		$cacheFilePath = $this->cachePath . $this->filePath;

		if (file_exists($cacheFilePath) === false) {
			return $cache;
		}

		$content = json_decode(file_get_contents($cacheFilePath));

		$entries = [];

		foreach ($content as $key => $entry) {
			$tplCacheEntry = new TemplateCacheEntry();

			$tplCacheEntry->changeTime = $entry->changeTime;
			$tplCacheEntry->path = $entry->path;
			$tplCacheEntry->size = $entry->size;

			$entries[$key] = $tplCacheEntry;
		}

		return $entries;
	}

	protected function saveCacheFile()
	{
		if ($this->cacheChanged === false) {
			return;
		}

		$cacheFilePath = $this->cachePath . $this->filePath;

		$fp = file_put_contents($cacheFilePath, json_encode($this->registry));

		if ($fp === false) {
			throw new Exception('Could not write template cache-file: ' . $cacheFilePath);
		}
	}

	public function getCachedTplFile(string $tplFile): ?TemplateCacheEntry
	{
		if ($this->registry === null || array_key_exists($tplFile, $this->registry) === false) {
			return null;
		}

		return $this->registry[$tplFile];
	}

	public function addCachedTplFile(string $tplFile, ?TemplateCacheEntry $currentCacheEntry, string $compiledTemplateContent): TemplateCacheEntry
	{
		// NEW HERE
		$cacheFileName = ($currentCacheEntry !== null) ? $currentCacheEntry->path : uniqid() . self::CACHE_SUFFIX;
		$cacheFilePath = $this->cachePath . $cacheFileName;

		if (stream_resolve_include_path($cacheFilePath) === true && is_writable($cacheFilePath) === false) {
			throw new Exception('Cache file is not writable: ' . $cacheFilePath);
		}

		$fp = @fopen($cacheFilePath, 'w');

		if ($fp !== false) {
			fwrite($fp, $compiledTemplateContent);
			fclose($fp);

			$this->saveOnDestruct = true;
		} else {
			throw new Exception('Could not cache template-file: ' . $cacheFilePath);
		}

		$fileSize = @filesize($tplFile);

		if (($changeTime = @filemtime($tplFile)) === false) {
			$changeTime = @filectime($tplFile);
		}

		$tplCacheEntry = new TemplateCacheEntry();

		$tplCacheEntry->path = $cacheFileName;
		$tplCacheEntry->size = $fileSize;
		$tplCacheEntry->changeTime = $changeTime;

		$this->registry[$tplFile] = $tplCacheEntry; //new TemplateCacheEntry($tplFile, $id, $size, $changeTime);
		$this->cacheChanged = true;

		return $tplCacheEntry;
	}

	public function __destruct()
	{
		if ($this->saveOnDestruct === false) {
			return;
		}

		$this->saveCacheFile();
	}
}
/* EOF */