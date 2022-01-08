<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use Exception;
use LogicException;

class LocaleHandler
{
	private static ?LocaleHandler $instance = null;
	private bool $isInitialized = false;

	private array $languageBlocks = [];
	private array $loadedLangFiles = [];

	public static function getInstance(): LocaleHandler
	{
		if (is_null(LocaleHandler::$instance)) {
			LocaleHandler::$instance = new LocaleHandler();
		}

		return LocaleHandler::$instance;
	}

	private function __construct() { }

	public function init(EnvironmentSettingsModel $environmentSettingsModel, RequestHandler $requestHandler)
	{
		if ($this->isInitialized) {
			throw new LogicException('LocaleHandler is already initialized');
		}
		$this->isInitialized = true;

		date_default_timezone_set($environmentSettingsModel->getTimezone());

		$availableLanguages = $environmentSettingsModel->getAvailableLanguages();
		$activeLanguage = $requestHandler->getLanguage();

		if (!array_key_exists($activeLanguage, $availableLanguages)) {
			throw new Exception('Language ' . $activeLanguage . ' is not available');
		}

		setlocale(LC_ALL, $availableLanguages[$activeLanguage]);
		setlocale(LC_NUMERIC, 'en_US');
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
		require_once $filePath;

		foreach ($txt as $key => $val) {
			$this->languageBlocks[$key] = $val;
		}
	}

	public function getText(string $key, array $replacements = []): string
	{
		if (!array_key_exists($key, $this->languageBlocks)) {
			throw new Exception('Missing language fragment for ' . $key);
		}

		$block = $this->languageBlocks[$key];

		if (count($replacements) > 0) {
			$search = [];
			$replace = [];

			foreach ($replacements as $k => $v) {
				$search[] = "[" . strtoupper($k) . "]";
				$replace[] = $v;
			}

			$block = str_ireplace($search, $replace, $block);
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