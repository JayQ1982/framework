<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\autoloader;

use Exception;
use LogicException;
use framework\common\JsonUtils;

class Autoloader
{
	private static ?Autoloader $instance = null;

	private string $cacheFilePath;
	private array $cachedClasses = [];
	/** @var AutoloaderPathModel[] */
	private array $paths = [];
	private bool $cachedClassesChanged = false;

	public static function register(string $cacheFilePath = ''): Autoloader
	{
		if (!is_null(Autoloader::$instance)) {
			throw new LogicException('Autoloader is already registered');
		}

		Autoloader::$instance = new Autoloader($cacheFilePath);
		spl_autoload_register([Autoloader::$instance, 'doAutoload']);

		return Autoloader::$instance;
	}

	private function __construct(string $cacheFilePath = '')
	{
		$this->cacheFilePath = trim($cacheFilePath);
		if (!$this->checkIfCacheDirectoryExists($cacheFilePath)) {
			return;
		}
		$this->cachedClasses = $this->initCachedClasses($cacheFilePath);
	}

	private function checkIfCacheDirectoryExists(string $cacheFilePath): bool
	{
		if ($cacheFilePath === '') {
			return true;
		}
		$dir = dirname($cacheFilePath);
		if (!is_dir($dir)) {
			throw new Exception('Cache-Directory ' . $dir . ' does not exist');
		}

		return true;
	}

	private function initCachedClasses(string $cacheFilePath): array
	{
		if ($cacheFilePath === '' || !file_exists($cacheFilePath)) {
			return [];
		}

		$jsonString = file_get_contents($cacheFilePath);

		return json_decode(json: $jsonString, associative: true, flags: JSON_THROW_ON_ERROR);
	}

	public function addPath(AutoloaderPathModel $autoloaderPathModel): void
	{
		$this->paths[] = $autoloaderPathModel;
	}

	private function doAutoload(string $className): bool
	{
		$includePath = $this->getPathFromCache($className);
		if (!is_null($includePath)) {
			require_once $includePath;

			return true;
		}

		foreach ($this->paths as $autoloaderPathModel) {
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
			if ($phpFilePathRemove !== '') {
				$phpFilePath = preg_replace(
					pattern: '#' . $phpFilePathRemove . '#',
					replacement: '',
					subject: $phpFilePath
				);
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
		require_once $includePath;

		$this->cachedClasses[$className] = $includePath;
		$this->cachedClassesChanged = true;
	}

	public function __destruct()
	{
		if ($this->cacheFilePath === '' || !$this->cachedClassesChanged) {
			return;
		}

		file_put_contents(filename: $this->cacheFilePath, data: JsonUtils::convertToJsonString(valueToConvert: $this->cachedClasses));
	}
}