<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\html;

use framework\security\CspNonce;
use framework\template\template\DirectoryTemplateCache;
use framework\template\template\TemplateEngine;

class HtmlSnippet
{
	private string $cachePath;
	private string $snippetBaseDirectory;
	private string $htmlSnippetFilePath;
	private array $replacements = [];

	public function __construct(string $cachePath, string $snippetBaseDirectory, string $htmlSnippetFilePath)
	{
		$this->cachePath = $cachePath;
		$this->snippetBaseDirectory = $snippetBaseDirectory;
		$this->htmlSnippetFilePath = $htmlSnippetFilePath;
	}

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

	public function addRawPlaceholder(string $placeholderName, mixed $value): void
	{
		$this->replacements[$placeholderName] = $value;
	}

	public function render(): string
	{
		if (!array_key_exists(key: 'cspNonce', array: $this->replacements)) {
			$this->replacements['cspNonce'] = CspNonce::get();
		}

		$tplCache = new DirectoryTemplateCache(
			cachePath: $this->cachePath,
			templateBaseDirectory: $this->snippetBaseDirectory
		);
		$renderer = new TemplateEngine(tplCacheInterface: $tplCache, tplNsPrefix: 'tst');

		return $renderer->getResultAsHtml(tplFile: $this->htmlSnippetFilePath, tplVars: $this->replacements);
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