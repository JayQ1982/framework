<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use Exception;
use LogicException;
use framework\html\HtmlDocument;

class ContentHandler
{
	private static ?ContentHandler $instance = null;
	private Core $core;
	private int $httpStatusCode = HttpStatusCodes::HTTP_OK;
	private string $content = '';
	private string $contentType = HttpResponse::TYPE_HTML;
	private bool $suppressCspHeader = false;
	private bool $isInitialized = false;

	public static function getInstance(Core $core): ContentHandler
	{
		if (is_null(ContentHandler::$instance)) {
			ContentHandler::$instance = new ContentHandler($core);
		}

		return ContentHandler::$instance;
	}

	private function __construct(Core $core)
	{
		$this->core = $core;
		$requestHandler = $core->getRequestHandler();

		$defaultContentType = trim($requestHandler->getContentType());
		if ($defaultContentType !== '') {
			$this->contentType = $defaultContentType;
		}
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

	public function init(): void
	{
		if ($this->isInitialized) {
			throw new LogicException('ContentHandler is already initialized');
		}
		$this->isInitialized = true;
		$core = $this->core;

		ob_start();
		ob_implicit_flush(false);

		$this->loadLocalizedText($core->getRequestHandler(), $core->getLocaleHandler());
		$this->executePHP($core);
		if (!$this->hasContent() && $this->contentType === HttpResponse::TYPE_HTML) {
			$this->setContent(HtmlDocument::getInstance($core)->render());
		}
		$outputBufferContents = trim(ob_get_clean());
		if ($outputBufferContents !== '') {
			$this->setContent($outputBufferContents);
		}
	}

	public function setContent(string $contentString): void
	{
		if ($this->hasContent()) {
			throw new LogicException('Content is already set. You are not allowed to overwrite it.');
		}
		$this->content = $contentString;
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
		if(!$this->hasContent()) {
			$baseView->execute();
		}
	}

	public function hasContent(): bool
	{
		return trim($this->content) !== '';
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