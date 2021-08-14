<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\html;

use framework\core\CoreProperties;
use framework\core\LocaleHandler;
use framework\security\CspNonce;
use framework\template\template\DirectoryTemplateCache;
use framework\template\template\TemplateEngine;

class HtmlSnippet
{
	private array $replacements = [];

	public function addText(string $placeholderName, ?string $content, bool $isEncodedForRendering): void
	{
		if (is_null(value: $content)) {
			$this->replacements[$placeholderName] = null;

			return;
		}
		$this->replacements[$placeholderName] = $isEncodedForRendering ? $content : HtmlDocument::htmlEncode(value: $content);
	}

	public function addBooleanValue(string $placeholderName, bool $booleanValue): void
	{
		$this->replacements[$placeholderName] = $booleanValue;
	}

	public function addDataObject(string $placeholderName, ?HtmlDataObject $htmlDataObject)
	{
		$this->replacements[$placeholderName] = is_null($htmlDataObject) ? null : $htmlDataObject->getData();
	}

	public function render(LocaleHandler $localeHandler, CoreProperties $coreProperties, string $htmlSnippetFilePath): string
	{
		$this->replacements['_localeHandler'] = $localeHandler;
		if (!array_key_exists(key: 'cspNonce', array: $this->replacements)) {
			$this->replacements['cspNonce'] = CspNonce::get();
		}

		$tplCache = new DirectoryTemplateCache(
			cachePath: $coreProperties->getSiteCacheDir(),
			baseDir: $coreProperties->getSiteContentDir()
		);
		$renderer = new TemplateEngine(tplCacheInterface: $tplCache, tplNsPrefix: 'tst');

		return $renderer->getResultAsHtml(tplFile: $htmlSnippetFilePath, tplVars: $this->replacements);
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
}