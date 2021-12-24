<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\common;

use framework\core\Core;
use framework\core\LocaleHandler;
use framework\core\RequestHandler;
use framework\template\template\DirectoryTemplateCache;
use framework\template\template\TemplateEngine;

class SnippetRenderer
{
	private static ?SnippetRenderer $instance = null;
	private TemplateEngine $renderer;
	private string $snippetsDirectory;

	private function __construct(Core $core)
	{
		$cacheDir = $core->getCoreProperties()->getSiteCacheDir();
		$tplCache = new DirectoryTemplateCache($cacheDir, $core->getCoreProperties()->getSiteContentDir());
		$this->renderer = new TemplateEngine($tplCache, 'tst');
		$this->snippetsDirectory = RequestHandler::getInstance()->getAreaDir() . 'snippets' . DIRECTORY_SEPARATOR;
	}

	public static function getInstance(Core $core): SnippetRenderer
	{
		if (is_null(SnippetRenderer::$instance)) {
			SnippetRenderer::$instance = new SnippetRenderer($core);
		}

		return SnippetRenderer::$instance;
	}

	public function getHtml(string $templateName, LocaleHandler $l18n, array $placeholders = []): string
	{
		$templateFile = $this->snippetsDirectory . $templateName . '.html';

		$tplVars = $placeholders;
		$tplVars['_localeHandler'] = $l18n;

		return $this->renderer->getResultAsHtml($templateFile, $tplVars);
	}
}