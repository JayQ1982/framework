<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\autoloader;

use Exception;

class Autoloader
{
	private ?string $cacheFilePath;
	private array $cachedClasses = [];
	/** @var AutoloaderPathModel[] */
	private array $paths = [];
	private bool $cachedClassesChanged = false;

	public function __construct(?string $cacheFilePath = null)
	{
		$this->cacheFilePath = $cacheFilePath;
		if (!$this->checkIfCacheDirectoryExists($cacheFilePath)) {
			return;
		}
		$this->cachedClasses = $this->initCachedClasses($cacheFilePath);
	}

	private function checkIfCacheDirectoryExists(?string $cacheFilePath): bool
	{
		if (is_null($cacheFilePath) || trim($cacheFilePath) === '') {
			return true;
		}
		$dir = dirname($cacheFilePath);
		if (!is_dir($dir)) {
			throw new Exception('Cache-Directory ' . $dir . ' does not exist');
		}

		return true;
	}

	private function initCachedClasses(?string $cacheFilePath): array
	{
		if (is_null($cacheFilePath) || trim($cacheFilePath) === '' || !file_exists($cacheFilePath)) {
			return [];
		}

		$serialized = file_get_contents($cacheFilePath);

		return json_decode($serialized, true);
	}

	public function register(): void
	{
		spl_autoload_register([$this, 'doAutoload']);
	}

	public function addPath(AutoloaderPathModel $autoloaderPathModel): void
	{
		$this->paths[] = $autoloaderPathModel;
	}

	private function doAutoload(string $className): bool
	{
		$includePath = $this->getPathFromCache($className);
		if (!is_null($includePath)) {
			/** @noinspection PhpIncludeInspection */
			require_once $includePath;

			return true;
		}

		foreach ($this->paths as $autoloaderPathModel) {
			$delimiter = null;

			$path = $autoloaderPathModel->getPath();
			$mode = $autoloaderPathModel->getMode();

			if ($mode === AutoloaderPathModel::MODE_NAMESPACE) {
				$delimiter = '\\';
			} else if ($mode === AutoloaderPathModel::MODE_UNDERSCORE) {
				$delimiter = '_';
			} else {
				throw new Exception('Unknown mode for path "' . $path . '": ' . $mode);
			}

			$classPathParts = explode($delimiter, $className);
			$phpFilePath = implode(DIRECTORY_SEPARATOR, $classPathParts);

			$phpFilePathRemove = $autoloaderPathModel->getPhpFilePathRemove();
			if (!is_null($phpFilePathRemove) && trim($phpFilePathRemove) !== '') {
				$phpFilePath = preg_replace('#' . $phpFilePathRemove . '#', '', $phpFilePath);
			}

			foreach ($autoloaderPathModel->getFileSuffixList() as $fileSuffix) {
				$includePath = $path . $phpFilePath . $fileSuffix;

				$streamResolvedIncludePath = stream_resolve_include_path($includePath);
				if ($streamResolvedIncludePath === false) {
					continue;
				}

				$this->doInclude($includePath, $className);

				return true;
			}
		}

		return false;
	}

	private function getPathFromCache(string $className): ?string
	{
		if (!isset($this->cachedClasses[$className])) {
			return null;
		}

		$classPath = $this->cachedClasses[$className];

		if (file_exists($classPath)) {
			return $classPath;
		}

		if (file_exists('phar://' . $classPath)) {
			return 'phar://' . $classPath;
		}

		return null;
	}

	private function doInclude(string $includePath, string $className): void
	{
		if (class_exists($className)) {
			return;
		}
		/** @noinspection PhpIncludeInspection */
		require_once $includePath;

		$this->cachedClasses[$className] = $includePath;
		$this->cachedClassesChanged = true;
	}

	public function __destruct()
	{
		if (is_null($this->cacheFilePath) || trim($this->cacheFilePath) === '' || !$this->cachedClassesChanged) {
			return;
		}

		$serialized = json_encode($this->cachedClasses);
		file_put_contents($this->cacheFilePath, $serialized);
	}
}