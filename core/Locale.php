<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use Exception;
use LogicException;

class Locale
{
	private static ?Locale $registeredInstance = null;
	private array $languageBlocks = [];
	private array $loadedLangFiles = [];

	public static function get(): Locale
	{
		return Locale::$registeredInstance;
	}

	public static function register(): void
	{
		new Locale();
	}

	private function __construct()
	{
		if (!is_null(value: Locale::$registeredInstance)) {
			throw new LogicException(message: 'Locale is already registered');
		}
		Locale::$registeredInstance = $this;
		$requestLanguageCode = Request::get()->language->code;
		$activeLanguage = EnvironmentSettingsModel::get()->availableLanguages->getLanguageByCode(languageCode: $requestLanguageCode);
		if (is_null(value: $activeLanguage)) {
			throw new Exception(message: 'Language ' . $requestLanguageCode . ' is not available');
		}
		setlocale(category: LC_ALL, locales: $activeLanguage->locale);
		setlocale(category: LC_NUMERIC, locales: 'en_US');
	}

	public function loadLanguageFile(string $filePath): void
	{
		if (in_array(needle: $filePath, haystack: $this->loadedLangFiles)) {
			return;
		}

		if ((int)filesize(filename: $filePath) === 0) {
			return;
		}

		$this->parseLanguageFile(filePath: $filePath);
		$this->loadedLangFiles[] = $filePath;
	}

	private function parseLanguageFile(string $filePath): void
	{
		$txt = [];
		require_once $filePath;

		foreach ($txt as $key => $val) {
			$this->languageBlocks[$key] = $val;
		}
	}

	public function getText(string $key, array $replacements = []): string
	{
		if (!array_key_exists(key: $key, array: $this->languageBlocks)) {
			throw new Exception(message: 'Missing language fragment for ' . $key);
		}

		$block = $this->languageBlocks[$key];

		if (count(value: $replacements) > 0) {
			$search = [];
			$replace = [];

			foreach ($replacements as $k => $v) {
				$search[] = "[" . strtoupper(string: $k) . "]";
				$replace[] = $v;
			}

			$block = str_ireplace(search: $search, replace: $replace, subject: $block);
		}

		return $block;
	}

	public function getAllText(): array
	{
		return $this->languageBlocks;
	}

	public function getLoadedLangFiles(): array
	{
		return $this->loadedLangFiles;
	}
}