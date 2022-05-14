<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\html;

use framework\Core;
use framework\core\EnvironmentSettingsModel;
use framework\core\Request;
use framework\exception\NotFoundException;
use framework\security\CspNonce;
use framework\security\CsrfToken;
use framework\template\template\DirectoryTemplateCache;
use framework\template\template\TemplateEngine;

class HtmlDocument
{
	private static ?HtmlDocument $instance = null;
	private string $templateName = 'default';
	private string $contentFileName;
	public readonly HtmlReplacementCollection $replacements;
	private array $activeHtmlIds = [];

	public static function get(): HtmlDocument
	{
		if (is_null(value: HtmlDocument::$instance)) {
			HtmlDocument::$instance = new HtmlDocument();
		}

		return HtmlDocument::$instance;
	}

	private function __construct()
	{
		$fileTitle = Request::get()->fileTitle;
		$this->contentFileName = $fileTitle;
		$this->replacements = new HtmlReplacementCollection();
		$environmentSettingsModel = EnvironmentSettingsModel::get();

		$copyright = $environmentSettingsModel->copyrightYear;
		$replacements = $this->replacements;
		$replacements->addEncodedText(identifier: 'bodyid', content: 'body_' . $fileTitle);
		$replacements->addEncodedText(identifier: 'language', content: Request::get()->language->code);
		$replacements->addEncodedText(identifier: 'charset', content: 'UTF-8');
		$replacements->addEncodedText(identifier: 'copyright', content: ($copyright < (int)date(format: 'Y')) ? $copyright . '-' . date(format: 'Y') : $copyright);
		$replacements->addEncodedText(identifier: 'robots', content: $environmentSettingsModel->robots);
		$replacements->addEncodedText(identifier: 'scripts', content: '');
		$replacements->addEncodedText(identifier: 'cspNonce', content: CspNonce::get());
		$replacements->addEncodedText(identifier: 'csrfField', content: CsrfToken::renderAsHiddenPostField());
	}

	public function setTemplateName(string $templateName): void
	{
		$this->templateName = $templateName;
	}

	public function setContentFileName(string $contentFileName): void
	{
		$this->contentFileName = $contentFileName;
	}

	public function setActiveHtmlId(int $key, string $val): void
	{
		$this->activeHtmlIds[$key] = $val;
	}

	public function isActiveHtmlIdSet(int $key): bool
	{
		return array_key_exists(key: $key, array: $this->activeHtmlIds);
	}

	public function render(): string
	{
		$request = Request::get();
		$viewDirectory = $request->route->getViewDirectory();
		$contentFileDirectory = $viewDirectory . 'html/';
		if (!is_null(value: $request->getFileGroup())) {
			$contentFileDirectory .= $request->getFileGroup() . '/';
		}
		if ($this->contentFileName === '') {
			throw new NotFoundException();
		}
		$fullContentFilePath = $contentFileDirectory . $this->contentFileName . '.html';
		if (!is_file(filename: $fullContentFilePath)) {
			throw new NotFoundException();
		}
		$this->replacements->set(identifier: 'this', htmlReplacement: HtmlReplacement::encodedText(content: $fullContentFilePath));
		$templateName = $this->templateName;
		$templateFilePath = $viewDirectory . 'templates/' . $templateName . '.html';
		if ($templateName === '' || !is_file(filename: $templateFilePath)) {
			$templateFilePath = $fullContentFilePath;
		}
		$core = Core::get();
		$tplEngine = new TemplateEngine(
			tplCacheInterface: new DirectoryTemplateCache(
				cachePath: $core->cacheDirectory,
				templateBaseDirectory: $core->viewDirectory
			),
			tplNsPrefix: 'tst'
		);
		$htmlAfterReplacements = $tplEngine->getResultAsHtml(tplFile: $templateFilePath, dataPool: $this->replacements->getArrayObject());

		return preg_replace_callback(
			pattern: '/(\s+id="nav-(.+?)")(\s+class="(.+?)")?/',
			callback: [$this, 'setCSSActive'],
			subject: $htmlAfterReplacements
		);
	}

	private function setCSSActive(array $m): string
	{
		if (!in_array(needle: $m[2], haystack: $this->activeHtmlIds)) {
			// The id is not within activeHtmlIds, so we just return the whole unmodified string
			return $m[0];
		}

		// The id is within activeHtmlIds, so we need to add the "active" class
		return $m[1] . ' class="' . (isset($m[3]) ? $m[4] . ' ' : '') . 'active"';
	}
}