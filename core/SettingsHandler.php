<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use Exception;
use framework\common\JsonUtils;
use stdClass;

class SettingsHandler
{
	private string $settingsPath;
	private string $cacheFile;
	private stdClass $settingsData;
	private array $replace;
	private int $cachedFileTime = 0;
	private bool $cacheChanged = false;

	public function __construct(CoreProperties $coreProperties, string $host)
	{
		$this->settingsPath = $coreProperties->getSiteSettingsDir();
		$this->cacheFile = $coreProperties->getSiteCacheDir() . 'settings.cache';
		$this->settingsData = new stdClass();
		$this->replace = [
			'${fwRoot}'   => $coreProperties->getFwRoot(),
			'${siteRoot}' => $coreProperties->getSiteRoot(),
			'${host}'     => $host,
		];

		$this->loadSettingsFromCache();
	}

	private function loadSettingsFromCache(): void
	{
		if (!file_exists($this->cacheFile)) {
			return;
		}

		$cacheFileContent = file_get_contents($this->cacheFile);

		if (strlen($cacheFileContent) === 0) {
			return;
		}

		$cachedData = unserialize($cacheFileContent);

		$this->settingsData = $cachedData['settings'];
		$this->cachedFileTime = (int)$cachedData['cachetime'];
	}

	public function exists(string $property): bool
	{
		return file_exists($this->settingsPath . $property . '.json');
	}

	public function get(string $property): stdClass
	{
		$filePath = $this->settingsPath . $property . '.json';
		if (!$this->exists($property)) {
			throw new Exception('File "' . $filePath . '" does not exist!');
		}

		/* Property does not exist OR cache file is older than the json file here */
		if (!isset($this->settingsData->{$property}) || filemtime($filePath) > $this->cachedFileTime) {
			$this->settingsData->{$property} = $this->loadSettingsFromFile($property . '.json');
			$this->cacheChanged = true;
		}

		return $this->settingsData->{$property};
	}

	private function loadSettingsFromFile(string $fileName): ?stdClass
	{
		$filePath = $this->settingsPath . $fileName;

		if (!file_exists($filePath)) {
			throw new Exception('Settings file does not exist: ' . $filePath);
		}

		$content = file_get_contents($filePath);

		if ($content === false || strlen($content) <= 0) {
			throw new Exception('Failed to load settings from ' . $filePath);
		}

		$settingsObj = JsonUtils::decode($content, false, false);

		if (is_null($settingsObj)) {
			throw new Exception('Invalid JSON code in settings file: ' . $filePath);
		}

		// Replace some fw placeholders
		$this->interpolateObj($settingsObj, $this->replace);

		return $settingsObj;
	}

	private function interpolateObj(stdClass $settingsObj, array $replace = []): void
	{
		if (count($replace) === 0) {
			return;
		}

		foreach ($settingsObj as $k => $v) {
			if (is_object($settingsObj->$k) && $settingsObj->$k instanceof stdClass) {
				$this->interpolateObj($settingsObj->$k, $replace);
			} else if (is_array($settingsObj->$k)) {
				$this->interpolateArray($settingsObj->$k, $replace);
			} else if (is_string($v) === true) {
				$settingsObj->$k = strtr($v, $replace);
			}
		}
	}

	private function interpolateArray(array $settingsArray, array $replace = []): void
	{
		if (count($replace) === 0) {
			return;
		}

		foreach ($settingsArray as $k => $v) {
			if (is_object($settingsArray[$k]) && $settingsArray[$k] instanceof stdClass) {
				$this->interpolateObj($settingsArray[$k], $replace);
			} else if (is_array($settingsArray[$k])) {
				$this->interpolateArray($settingsArray[$k], $replace);
			} else {
				$settingsObj[$k] = strtr($v, $replace);
			}
		}
	}

	public function __destruct()
	{
		if (!$this->cacheChanged) {
			return;
		}

		file_put_contents($this->cacheFile, serialize([
			'cachetime' => time(),
			'settings'  => $this->settingsData,
		]));
	}
}