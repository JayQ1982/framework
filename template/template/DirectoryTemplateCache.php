<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\template;

class DirectoryTemplateCache extends TemplateCacheStrategy
{
	protected const CACHE_SUFFIX = '.php';
	protected string $templateBaseDirectory;
	protected int $baseDirLength;

	function __construct(string $cachePath, string $templateBaseDirectory)
	{
		parent::__construct($cachePath);

		$this->templateBaseDirectory = $templateBaseDirectory;
		$this->baseDirLength = strlen($templateBaseDirectory);
	}

	public function getCachedTplFile(string $tplFile): ?TemplateCacheEntry
	{
		$cacheFileName = $this->getCacheFileName($tplFile);
		$cacheFilePath = $this->cachePath . $cacheFileName;

		if (file_exists($cacheFilePath) === false) {
			return null;
		}

		$changeTime = filemtime($cacheFilePath);
		if ($changeTime === false) {
			$changeTime = filectime($cacheFilePath);
		}

		return new TemplateCacheEntry($cacheFileName, $changeTime, -1);
	}

	public function addCachedTplFile(string $tplFile, ?TemplateCacheEntry $currentCacheEntry, string $compiledTemplateContent): TemplateCacheEntry
	{
		$cacheFileName = $this->getCacheFileName($tplFile);
		$cacheFilePath = $this->cachePath . $cacheFileName;

		if (file_exists($cacheFilePath) === true) {
			file_put_contents($cacheFilePath, $compiledTemplateContent);

			return new TemplateCacheEntry($cacheFileName, time(), -1);
		}

		$fileLocation = pathinfo($cacheFilePath, PATHINFO_DIRNAME);

		if (is_dir($fileLocation) === false) {
			mkdir($fileLocation, 0777, true);
		}

		file_put_contents($cacheFilePath, $compiledTemplateContent);

		return new TemplateCacheEntry($cacheFileName, time(), -1);
	}

	protected function getCacheFileName(string $tplFile): string
	{
		$offset = str_contains($tplFile, $this->templateBaseDirectory) ? $this->baseDirLength : 0;

		return preg_replace(
			pattern: '/\.\w+$/',
			replacement: DirectoryTemplateCache::CACHE_SUFFIX,
			subject: substr($tplFile, $offset)
		);
	}
}