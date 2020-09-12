<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use Exception;
use framework\security\CspNonce;
use framework\security\CsrfToken;

class ContentHandler
{
	private Core $core;
	private string $htmlFileName;
	private string $contentType = HttpResponse::TYPE_HTML;
	private array $placeholders = [];
	private array $navigationLevels = [];
	private ?string $template = 'default';
	private string $content = '';
	private int $httpStatusCode = HttpStatusCodes::HTTP_OK;
	private bool $suppressCSPheader = false;

	public function __construct(Core $core)
	{
		$this->core = $core;
		$requestHandler = $core->getRequestHandler();
		$this->htmlFileName = trim($requestHandler->getFileTitle());
		$defaultContentType = trim($requestHandler->getContentType());
		if ($defaultContentType !== '') {
			$this->contentType = $defaultContentType;
		}
	}

	public function setContentType(string $contentType): void
	{
		if (!in_array($contentType, HttpResponse::CONTENT_TYPES_WITH_CHARSET + ['pdf', 'png'])) {
			throw new Exception('Unknown contentType: ' . $contentType);
		}
		$this->contentType = $contentType;
	}

	public function setContent(): void
	{
		$core = $this->core;
		$requestHandler = $core->getRequestHandler();
		$localeHandler = $core->getLocaleHandler();
		$environmentHandler = $core->getEnvironmentHandler();
		$httpRequest = $core->getHttpRequest();
		$errorHandler = $core->getErrorHandler();

		ob_start();
		ob_implicit_flush(0);

		$this->loadLocalizedText($requestHandler, $localeHandler);
		$this->setCoreReplacements($requestHandler, $environmentHandler, $httpRequest);
		$this->setStatePlaceholder($httpRequest, $localeHandler);
		$this->executePHP($core);

		if ($this->contentType === HttpResponse::TYPE_HTML) {
			// Load HTML file and replace placeholders, set by the php file(s)
			$this->loadHTML($core);
		}

		$this->addContent(ob_get_clean());

		if (!$this->hasContent()) {
			$errorHandler->display_error(HttpStatusCodes::HTTP_NOT_FOUND, 'Die gewünschte Seite wurde nicht gefunden.', true);
		}
	}

	private function loadLocalizedText(RequestHandler $requestHandler, LocaleHandler $localeHandler): void
	{
		$dir = $requestHandler->getAreaDir() . 'language' . DIRECTORY_SEPARATOR . $requestHandler->getLanguage() . DIRECTORY_SEPARATOR;
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

	private function setCoreReplacements(RequestHandler $requestHandler, EnvironmentHandler $environmentHandler, HttpRequest $httpRequest): void
	{
		$copyrightYear = $environmentHandler->getCopyrightYear();

		$this->placeholders['bodyid'] = 'body_' . $requestHandler->getFileTitle();
		$this->placeholders['language'] = $requestHandler->getLanguage();
		$this->placeholders['charset'] = 'UTF-8';
		$this->placeholders['copyright'] = ($copyrightYear < date('Y')) ? $copyrightYear . '-' . date('Y') : $copyrightYear;
		$this->placeholders['protocol'] = $httpRequest->getProtocol();
		$this->placeholders['robots'] = $environmentHandler->getRobots();
		$this->placeholders['scripts'] = '';
		$this->placeholders['result'] = '';
		$this->placeholders['cspNonce'] = CspNonce::get();
		$this->placeholders['csrfField'] = CsrfToken::renderAsHiddenPostField();
	}

	private function setStatePlaceholder(HttpRequest $httpRequest, LocaleHandler $localeHandler): void
	{
		$ac = $httpRequest->getInputString('ac');

		if (is_null($ac)) {
			return;
		}

		$this->placeholders['result'] = '<p class="' . $ac . '">' . $localeHandler->getText($ac) . '</p>';
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

		$errorHandler = $core->getErrorHandler();
		if (!is_subclass_of($phpClassName, 'framework\core\baseView')) {
			$errorHandler->display_error(HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR, 'The class ' . $phpClassName . ' must extend framework\core\baseView.');
		}

		/** @var baseView $viewClass */
		$viewClass = new $phpClassName($core);
		if (!$viewClass->hasContent()) {
			$viewClass->execute();
		}
		$individualHtmlFileName = $viewClass->getIndividualHtmlFileName();
		if (!is_null($individualHtmlFileName)) {
			$this->htmlFileName = $individualHtmlFileName;
		}
		$this->addContent($viewClass->getContent());
		$this->addPlaceholders($viewClass->getPlaceholders());
		$this->addNavigationLevels($viewClass->getNavigationLevels());
	}

	public function getPlaceholders(): array
	{
		return $this->placeholders;
	}

	public function getNavigationLevels(): array
	{
		return $this->navigationLevels;
	}

	public function setTemplate(string $templateName): void
	{
		$this->template = $templateName;
	}

	public function addContent(?string $content): void
	{
		$content = trim($content);
		if ($content === '') {
			return;
		}

		$this->content .= $content;
	}

	private function addPlaceholders(array $placeholders): void
	{
		foreach ($placeholders as $key => $val) {
			$this->placeholders[$key] = $val;
		}
	}

	private function addNavigationLevels(array $navigationLevels): void
	{
		foreach ($navigationLevels as $key => $val) {
			$this->navigationLevels[$key] = $val;
		}
	}

	private function loadHTML(Core $core): void
	{
		$pageHandler = new PageHandler($core, $this->htmlFileName, $this->template, $this->placeholders, $this->navigationLevels);
		$this->addContent($pageHandler->getContent());
	}

	public function hasContent(): bool
	{
		return (trim($this->content) !== '');
	}

	public function getContentType(): string
	{
		return $this->contentType;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function getHttpStatusCode(): int
	{
		return $this->httpStatusCode;
	}

	public function suppressCSPheader(): void
	{
		$this->suppressCSPheader = true;
	}

	public function isSuppressCSPheader(): bool
	{
		return $this->suppressCSPheader;
	}
}
/* EOF */