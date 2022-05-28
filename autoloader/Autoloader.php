<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\autoloader;

use Exception;
use framework\common\JsonUtils;
use LogicException;

class Autoloader
{
	private static ?Autoloader $registeredInstance = null;

	private string $cacheFilePath;
	private array $cachedClasses = [];
	/** @var AutoloaderPathModel[] */
	private array $paths = [];
	private bool $cachedClassesChanged = false;

	public static function register(string $cacheFilePath = ''): Autoloader
	{
		if (!is_null(value: Autoloader::$registeredInstance)) {
			throw new LogicException(message: 'Autoloader is already registered.');
		}
		Autoloader::$registeredInstance = new Autoloader(cacheFilePath: $cacheFilePath);
		spl_autoload_register(callback: [Autoloader::$registeredInstance, 'doAutoload']);

		return Autoloader::$registeredInstance;
	}

	public static function get(): Autoloader
	{
		return Autoloader::$registeredInstance;
	}

	private function __construct(string $cacheFilePath = '')
	{
		$this->cacheFilePath = trim(string: $cacheFilePath);
		if (!$this->checkIfCacheDirectoryExists(cacheFilePath: $cacheFilePath)) {
			return;
		}
		$this->cachedClasses = $this->initCachedClasses(cacheFilePath: $cacheFilePath);
	}

	private function checkIfCacheDirectoryExists(string $cacheFilePath): bool
	{
		if ($cacheFilePath === '') {
			return true;
		}
		$dir = dirname(path: $cacheFilePath);
		if (!is_dir(filename: $dir)) {
			throw new Exception(message: 'Cache-Directory ' . $dir . ' does not exist');
		}

		return true;
	}

	private function initCachedClasses(string $cacheFilePath): array
	{
		if ($cacheFilePath === '' || !file_exists(filename: $cacheFilePath)) {
			return [];
		}

		$jsonString = file_get_contents(filename: $cacheFilePath);

		return json_decode(json: $jsonString, associative: true, flags: JSON_THROW_ON_ERROR);
	}

	public function addPath(AutoloaderPathModel $autoloaderPathModel): void
	{
		$this->paths[] = $autoloaderPathModel;
	}

	private function doAutoload(string $className): bool
	{
		$includePath = $this->getPathFromCache($className);
		if (!is_null(value: $includePath)) {
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
				throw new Exception(message: 'Unknown mode for path "' . $path . '": ' . $mode);
			}

			$classPathParts = explode(separator: $delimiter, string: $className);
			$phpFilePath = implode(separator: DIRECTORY_SEPARATOR, array: $classPathParts);

			$phpFilePathRemove = $autoloaderPathModel->phpFilePathRemove;
			if ($phpFilePathRemove !== '') {
				$phpFilePath = preg_replace(
					pattern: '#' . $phpFilePathRemove . '#',
					replacement: '',
					subject: $phpFilePath
				);
			}

			foreach ($autoloaderPathModel->fileSuffixList as $fileSuffix) {
				$includePath = $path . $phpFilePath . $fileSuffix;

				$streamResolvedIncludePath = stream_resolve_include_path(filename: $includePath);
				if ($streamResolvedIncludePath === false) {
					continue;
				}

				$this->doInclude(includePath: $includePath, className: $className);

				return true;
			}
		}

		return false;
	}

	private function getPathFromCache(string $className): ?string
	{
		if (!array_key_exists(key: $className, array: $this->cachedClasses)) {
			return null;
		}

		$classPath = $this->cachedClasses[$className];

		if (file_exists(filename: $classPath)) {
			return $classPath;
		}

		if (file_exists(filename: 'phar://' . $classPath)) {
			return 'phar://' . $classPath;
		}

		return null;
	}

	private function doInclude(string $includePath, string $className): void
	{
		if (class_exists(class: $className)) {
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