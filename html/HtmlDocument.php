<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\html;

use framework\core\Core;
use framework\datacheck\Sanitizer;
use framework\exception\NotFoundException;
use framework\security\CspNonce;
use framework\security\CsrfToken;
use framework\template\template\DirectoryTemplateCache;
use framework\template\template\TemplateEngine;

class HtmlDocument
{
	private static ?HtmlDocument $instance = null;
	private Core $core;
	private string $templateName = 'default';
	private string $contentFileName;
	private array $replacements = [];
	private array $activeHtmlIds = [];

	public static function getInstance(Core $core): HtmlDocument
	{
		if (is_null(HtmlDocument::$instance)) {
			HtmlDocument::$instance = new HtmlDocument(core: $core);
		}

		return HtmlDocument::$instance;
	}

	private function __construct(Core $core)
	{
		$this->core = $core;
		$requestHandler = $core->getRequestHandler();
		$environmentSettingsModel = $core->getEnvironmentSettingsModel();

		$fileTitle = Sanitizer::trimmedString($requestHandler->getFileTitle());
		$this->contentFileName = $fileTitle;

		$this->replacements['_fileTitle'] = $fileTitle;
		$this->replacements['_localeHandler'] = $core->getLocaleHandler();

		$copyright = $environmentSettingsModel->getCopyrightYear();
		$this->addText('bodyid', 'body_' . $fileTitle, true);
		$this->addText('language', $requestHandler->getLanguage(), true);
		$this->addText('charset', 'UTF-8', true);
		$this->addText('copyright', ($copyright < (int)date('Y')) ? $copyright . '-' . date('Y') : $copyright, true);
		$this->addText('robots', $environmentSettingsModel->getRobots(), true);
		$this->addText('scripts', '', true);
		$this->addText('cspNonce', CspNonce::get(), true);
		$this->addText('csrfField', CsrfToken::renderAsHiddenPostField(), true);
	}

	public function setTemplateName(string $templateName): void
	{
		$this->templateName = $templateName;
	}

	public function setContentFileName(string $contentFileName): void
	{
		$this->contentFileName = $contentFileName;
	}

	public function hasReplacement(string $name): bool
	{
		return array_key_exists($name, $this->replacements);
	}

	public function addText(string $placeholderName, ?string $content, bool $isEncodedForRendering): void
	{
		if (is_null($content)) {
			$this->replacements[$placeholderName] = null;

			return;
		}
		$this->replacements[$placeholderName] = $isEncodedForRendering ? $content : HtmlDocument::htmlEncode($content);
	}

	public function addBooleanValue(string $placeholderName, bool $booleanValue): void
	{
		$this->replacements[$placeholderName] = $booleanValue;
	}

	public function addDataObject(string $placeholderName, ?HtmlDataObject $htmlDataObject)
	{
		$this->replacements[$placeholderName] = is_null($htmlDataObject) ? null : $htmlDataObject->getData();
	}

	/**
	 * @param string     $placeholderName
	 * @param HtmlText[] $textElementsArray
	 */
	public function addTextElementsArray(string $placeholderName, array $textElementsArray): void
	{
		$this->replacements[$placeholderName] = [];
		foreach ($textElementsArray as $htmlText) {
			$this->replacements[$placeholderName][] = $htmlText->render();
		}
	}

	/**
	 * @param string                $placeholderName
	 * @param HtmlDataObject[]|null $htmlDataObjectsArray
	 */
	public function addDataObjectsArray(string $placeholderName, ?array $htmlDataObjectsArray): void
	{
		if (is_null($htmlDataObjectsArray)) {
			$this->replacements[$placeholderName] = null;

			return;
		}

		$this->replacements[$placeholderName] = [];
		foreach ($htmlDataObjectsArray as $htmlDataObject) {
			$this->replacements[$placeholderName][] = $htmlDataObject->getData();
		}
	}

	/**
	 * @param string $placeholderName
	 *
	 * @deprecated : Backwards compatibility
	 * @todo       : Replace all occurrences of <tst:if compare="xx" operator="NE" against="null">
	 */
	public function addNullAsReplacementValue(string $placeholderName): void
	{
		$this->replacements[$placeholderName] = null;
	}

	public function setActiveHtmlId(int $key, string $val): void
	{
		$this->activeHtmlIds[$key] = $val;
	}

	public function isActiveHtmlIdSet(int $key): bool
	{
		return array_key_exists($key, $this->activeHtmlIds);
	}

	public function render(): string
	{
		$core = $this->core;
		$requestHandler = $core->getRequestHandler();
		$contentFileDirectory = $requestHandler->getAreaDir() . 'html/';
		if (!is_null($requestHandler->getFileGroup())) {
			$contentFileDirectory .= $requestHandler->getFileGroup() . '/';
		}

		if ($this->contentFileName === '') {
			throw new NotFoundException($core, false);
		}

		$fullContentFilePath = $contentFileDirectory . $this->contentFileName . '.html';
		if (!is_file($fullContentFilePath)) {
			throw new NotFoundException($core, false);
		}
		$this->addText('this', $fullContentFilePath, true);

		$templateName = $this->templateName;
		$templateFilePath = $requestHandler->getAreaDir() . 'templates/' . $templateName . '.html';
		if ($templateName === '' || !is_file($templateFilePath)) {
			$templateFilePath = $fullContentFilePath;
		}
		$coreProperties = $core->getCoreProperties();
		$tplCache = new DirectoryTemplateCache($coreProperties->getSiteCacheDir(), $coreProperties->getSiteContentDir());
		$tplEngine = new TemplateEngine($tplCache, 'tst');
		$htmlAfterReplacements = $tplEngine->getResultAsHtml($templateFilePath, $this->replacements);

		return preg_replace_callback(
			pattern: '/(\s+id="nav-(.+?)")(\s+class="(.+?)")?/',
			callback: [$this, 'setCSSActive'],
			subject: $htmlAfterReplacements
		);
	}

	private function setCSSActive(array $m): string
	{
		if (!in_array($m[2], $this->activeHtmlIds)) {
			// The id is not within activeHtmlIds, so we just return the whole unmodified string
			return $m[0];
		}

		// The id is within activeHtmlIds, so we need to add the "active" class
		return $m[1] . ' class="' . (isset($m[3]) ? $m[4] . ' ' : '') . 'active"';
	}

	/**
	 * @param      $value
	 * @param bool $keepQuotes
	 *
	 * @return mixed
	 * @todo Make separate methods for separate input/return types.
	 */
	public static function htmlEncode($value, bool $keepQuotes = false): mixed
	{
		if (is_null($value)) {
			return ''; // It's for display, not for value-processing
		}

		if (is_scalar($value)) {
			return htmlspecialchars($value, ($keepQuotes ? ENT_NOQUOTES : ENT_QUOTES));
		}

		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$value[$key] = HtmlDocument::htmlEncode($val);
			}
		}

		return $value;
	}
}