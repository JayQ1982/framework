<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\html;

use framework\Core;
use framework\core\RequestHandler;
use framework\security\CspNonce;
use framework\template\template\DirectoryTemplateCache;
use framework\template\template\TemplateEngine;

readonly class HtmlSnippet
{
	public static function createForCurrentView(string $snippetName): HtmlSnippet
	{
		return new HtmlSnippet(
			htmlSnippetFilePath: RequestHandler::get()->route->viewDirectory . 'snippets' . DIRECTORY_SEPARATOR . $snippetName . '.html'
		);
	}

	public function __construct(
		private string                   $htmlSnippetFilePath,
		public HtmlReplacementCollection $replacements = new HtmlReplacementCollection()
	) {
	}

	public function render(): string
	{
		$htmlSnippetFilePath = $this->htmlSnippetFilePath;
		$replacements = $this->replacements;
		if (!$replacements->has(identifier: 'cspNonce')) {
			$replacements->addEncodedText(identifier: 'cspNonce', content: CspNonce::get());
		}
		$core = Core::get();

		return (new TemplateEngine(
			tplCacheInterface: new DirectoryTemplateCache(
				cachePath: $core->cacheDirectory,
				templateBaseDirectory: str_replace(
					search: $htmlSnippetFilePath,
					replace: $core->documentRoot,
					subject: $htmlSnippetFilePath
				)
			),
			tplNsPrefix: 'tst'
		))->getResultAsHtml(
			tplFile: $htmlSnippetFilePath,
			dataPool: $this->replacements->getArrayObject()
		);
	}
}