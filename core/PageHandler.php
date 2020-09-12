<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use framework\template\template\DirectoryTemplateCache;
use framework\template\template\TemplateEngine;

class PageHandler
{
	private Core $core;
	private string $htmlFile;
	private string $templateFile = '';
	private array $placeholders = [];
	private array $activeHtmlIds = [];
	private ?string $content = null;

	public function __construct(Core $core, string $htmlFileName, ?string $templateName, array $placeholders = [], array $activeHtmlIds = [])
	{
		$this->core = $core;
		$requestHandler = $core->getRequestHandler();

		$htmlFileDirectory = $requestHandler->getAreaDir() . 'html' . DIRECTORY_SEPARATOR;
		if (!is_null($requestHandler->getFileGroup())) {
			$htmlFileDirectory .= $requestHandler->getFileGroup() . DIRECTORY_SEPARATOR;
		}
		$this->htmlFile = $htmlFileDirectory . $htmlFileName . '.html';
		if ($this->htmlFile === '' || !is_file($this->htmlFile)) {
			return;
		}

		$this->templateFile = empty($templateName) ? '' : $requestHandler->getAreaDir() . 'templates' . DIRECTORY_SEPARATOR . $templateName . '.html';
		$this->placeholders = $placeholders;
		$this->activeHtmlIds = $activeHtmlIds;
		$this->content = $this->render();
	}

	public function render(): ?string
	{
		$core = $this->core;
		$coreProperties = $core->getCoreProperties();
		$requestHandler = $core->getRequestHandler();

		$tplCache = new DirectoryTemplateCache($coreProperties->getSiteCacheDir(), $coreProperties->getSiteContentDir());
		$tplEngine = new TemplateEngine($tplCache, 'tst');

		$templateFile = ($this->templateFile !== '' && is_file($this->templateFile)) ? $this->templateFile : $this->htmlFile;
		$tplVars = $this->placeholders;
		$tplVars['this'] = $this->htmlFile;
		$tplVars['_auth'] = $core->getAuthenticator();
		$tplVars['_fileName'] = $requestHandler->getFileName();
		$tplVars['_fileTitle'] = $requestHandler->getFileTitle();
		$tplVars['_localeHandler'] = $core->getLocaleHandler();
		$tplVars['_activeHtmlIds'] = $this->activeHtmlIds;
		$htmlAfterReplacements = $tplEngine->getResultAsHtml($templateFile, $tplVars);

		return preg_replace_callback('/(\s+id="nav-(.+?)")(\s+class="(.+?)")?/', [
			$this,
			'setCSSActive',
		], $htmlAfterReplacements);
	}

	private function setCSSActive($m): string
	{
		if (is_null($this->activeHtmlIds) || !in_array($m[2], $this->activeHtmlIds)) {
			// The id is not within activeHtmlIds, so we just return the whole unmodified string
			return $m[0];
		}

		// The id is within activeHtmlIds, so we need to add the "active" class
		return $m[1] . ' class="' . (isset($m[3]) ? $m[4] . ' ' : '') . 'active"';
	}

	public function getContent(): ?string
	{
		return $this->content;
	}
}
/* EOF */