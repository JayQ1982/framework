<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\template;

class DirectoryTemplateCache extends TemplateCacheStrategy
{
	const CACHE_SUFFIX = '.php';
	protected $baseDir;
	protected $baseDirLength;

	function __construct($cachePath, $baseDir = DIRECTORY_SEPARATOR)
	{
		parent::__construct($cachePath);

		$this->baseDir = $baseDir;
		$this->baseDirLength = strlen($baseDir);
	}

	public function getCachedTplFile(string $tplFile): ?TemplateCacheEntry
	{
		$cacheFileName = $this->getCacheFileName($tplFile);
		$cacheFilePath = $this->cachePath . $cacheFileName;

		if (file_exists($cacheFilePath) === false) {
			return null;
		}

		if (($changeTime = filemtime($cacheFilePath)) === false) {
			$changeTime = filectime($cacheFilePath);
		}

		return $this->createTemplateCacheEntry($cacheFileName, $changeTime, -1);
	}

	public function addCachedTplFile(string $tplFile, ?TemplateCacheEntry $currentCacheEntry, string $compiledTemplateContent): TemplateCacheEntry
	{
		$cacheFileName = $this->getCacheFileName($tplFile);
		$cacheFilePath = $this->cachePath . $cacheFileName;

		if (file_exists($cacheFilePath) === true) {
			file_put_contents($cacheFilePath, $compiledTemplateContent);

			return $this->createTemplateCacheEntry($cacheFileName, time(), -1);
		}

		$fileLocation = pathinfo($cacheFilePath, PATHINFO_DIRNAME);

		if (is_dir($fileLocation) === false) {
			mkdir($fileLocation, 0777, true);
		}

		file_put_contents($cacheFilePath, $compiledTemplateContent);

		return $this->createTemplateCacheEntry($cacheFileName, time(), -1);
	}

	protected function getCacheFileName(string $tplFile): string
	{
		$offset = (strpos($tplFile, $this->baseDir) !== false) ? $this->baseDirLength : 0;

		return preg_replace('/\.\w+$/', self::CACHE_SUFFIX, substr($tplFile, $offset));
	}

	protected function createTemplateCacheEntry($path, $changeTime, $size)
	{
		$templateCacheEntry = new TemplateCacheEntry();

		$templateCacheEntry->path = $path;
		$templateCacheEntry->changeTime = $changeTime;
		$templateCacheEntry->size = $size;

		return $templateCacheEntry;
	}
}
/* EOF */