<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use Exception;

class LocaleHandler
{
	private array $languageBlocks = [];
	private array $loadedLangFiles = [];

	public function __construct(EnvironmentHandler $environmentHandler, RequestHandler $requestHandler)
	{
		date_default_timezone_set($environmentHandler->getTimezone());

		$availableLanguages = $environmentHandler->getAvailableLanguages();
		$activeLanguage = $requestHandler->getLanguage();

		if (!isset($availableLanguages[$activeLanguage])) {
			throw new Exception('Invalid language: ' . $activeLanguage);
		}

		setlocale(LC_ALL, $availableLanguages[$activeLanguage]);
		if (setlocale(LC_NUMERIC, 'en_US') === false) {
			setlocale(LC_NUMERIC, 'en_US');
		}
	}

	public function loadLanguageFile(string $filePath): void
	{
		if (in_array($filePath, $this->loadedLangFiles)) {
			return;
		}

		if (filesize($filePath) === 0) {
			return;
		}

		$this->parseLanguageFile($filePath);
		$this->loadedLangFiles[] = $filePath;
	}

	private function parseLanguageFile(string $filePath): void
	{
		$txt = [];
		/** @noinspection PhpIncludeInspection */
		require_once $filePath;

		foreach ($txt as $key => $val) {
			$this->languageBlocks[$key] = $val;
		}
	}

	public function getText(string $key, array $vars = []): string
	{
		if (!array_key_exists($key, $this->languageBlocks)) {
			throw new Exception('Missing language fragment for ' . $key);
		}

		$block = $this->languageBlocks[$key];

		if (count($vars) > 0) {
			$search = [];
			$replace = [];

			foreach ($vars as $k => $v) {
				$search[] = "{{" . strtoupper($k) . "}}";
				$replace[] = $v;
			}

			$block = str_ireplace($search, $replace, $block);
		}

		return $block;
	}
}
/* EOF */