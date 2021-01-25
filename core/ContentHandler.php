<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use Exception;
use framework\exception\NotFoundException;
use framework\html\HtmlDocument;

class ContentHandler
{
	private Core $core;
	private int $httpStatusCode = HttpStatusCodes::HTTP_OK;
	private string $content = '';
	private string $contentType = HttpResponse::TYPE_HTML;
	private bool $suppressCspHeader = false;
	private ?HtmlDocument $htmlDocument;

	public function __construct(Core $core)
	{
		$this->core = $core;
		$requestHandler = $core->getRequestHandler();
		$localeHandler = $core->getLocaleHandler();
		$environmentHandler = $core->getEnvironmentHandler();

		$defaultContentType = trim($requestHandler->getContentType());
		if ($defaultContentType !== '') {
			$this->contentType = $defaultContentType;
		}
		$this->htmlDocument = ($this->contentType === HttpResponse::TYPE_HTML) ? new HtmlDocument($requestHandler, $localeHandler, $environmentHandler) : null;
	}

	public function setContentType(string $contentType): void
	{
		if (!in_array($contentType, HttpResponse::CONTENT_TYPES_WITH_CHARSET)) {
			throw new Exception('Unknown contentType: ' . $contentType);
		}
		$this->contentType = $contentType;
	}

	public function getContentType(): string
	{
		return $this->contentType;
	}

	public function setContent(): void
	{
		$core = $this->core;
		$requestHandler = $core->getRequestHandler();
		$localeHandler = $core->getLocaleHandler();
		$coreProperties = $core->getCoreProperties();

		ob_start();
		ob_implicit_flush(false);

		$this->loadLocalizedText($requestHandler, $localeHandler);
		$this->executePHP($core);
		if (!is_null($this->htmlDocument)) {
			// After php execution because php script can modify the htmlDocument properties
			$this->addContent($this->htmlDocument->render($requestHandler, $coreProperties));
		}

		$this->addContent(ob_get_clean());

		$this->checkContent();
	}

	public function addContent(?string $content): void
	{
		$content = trim($content);
		if ($content === '') {
			return;
		}

		$this->content .= $content;
	}

	public function setHttpStatusCode(int $httpStatusCode)
	{
		$this->httpStatusCode = $httpStatusCode;
	}

	public function getHttpStatusCode(): int
	{
		return $this->httpStatusCode;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	private function loadLocalizedText(RequestHandler $requestHandler, LocaleHandler $localeHandler): void
	{
		$dir = $requestHandler->getAreaDir() . 'language/' . $requestHandler->getLanguage() . '/';
		if (!is_dir($dir)) {
			return;
		}

		$langGlobal = $dir . 'global.lang.php';

		if (file_exists($langGlobal)) {
			$localeHandler->loadLanguageFile($langGlobal);
		}

		$langFile = $dir . $requestHandler->getFileTitle() . '.lang.php';
		if (file_exists($langFile)) {
			$localeHandler->loadLanguageFile($langFile);
		}
	}

	private function executePHP(Core $core): void
	{
		$requestHandler = $core->getRequestHandler();
		$phpClassName = 'site\\content\\' . $requestHandler->getArea() . '\\php\\';
		if (!is_null($requestHandler->getFileGroup())) {
			$phpClassName .= $requestHandler->getFileGroup() . '\\';
		}
		$phpClassName .= $requestHandler->getFileTitle();
		if (!class_exists($phpClassName)) {
			return;
		}

		if (!is_subclass_of($phpClassName, 'framework\core\baseView')) {
			throw new Exception('The class ' . $phpClassName . ' must extend framework\core\baseView.');
		}

		/** @var baseView $baseView */
		$baseView = new $phpClassName($this->core);
		if (!$baseView->hasContent()) {
			$baseView->execute();
		}
		$this->addContent($baseView->getContent());
	}

	public function getHtmlDocument(): HtmlDocument
	{
		return $this->htmlDocument;
	}

	public function hasContent(): bool
	{
		return trim($this->content) !== '';
	}

	private function checkContent()
	{
		if (!$this->hasContent()) {
			throw new NotFoundException($this->core, false);
		}
	}

	public function suppressCspHeader(): void
	{
		$this->suppressCspHeader = true;
	}

	public function isSuppressCspHeader(): bool
	{
		return $this->suppressCspHeader;
	}
}