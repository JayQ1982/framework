<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use Exception;
use framework\datacheck\Sanitizer;
use framework\exception\NotFoundException;
use framework\session\AbstractSessionHandler;
use LogicException;

class RequestHandler
{
	private static RequestHandler $instance;

	private array $pathParts;
	private int $countPathParts;
	private string $fileName;
	/** @var Route[] */
	private array $defaultRoutesByLanguageCode;
	private ?Route $route;
	private ?string $fileGroup = null;
	private array $routeVariables = [];
	private ?string $viewGroup = null;
	private ?string $viewDirectory = null;
	private string $defaultFileName = '';
	private ?string $acceptedExtension;
	private ?string $language = null;
	private ?string $contentType = null;
	private ?string $fileTitle = null;
	private ?array $pathVars = null;
	private ?string $fileExtension = null;

	public static function getInstance(): RequestHandler
	{
		return RequestHandler::$instance;
	}

	private function __construct() { }

	/**
	 * @param Core    $core
	 * @param Route[] $allRoutes
	 *
	 * @return RequestHandler
	 */
	public static function init(Core $core, array $allRoutes): RequestHandler
	{
		if (isset(RequestHandler::$instance)) {
			throw new LogicException(message: 'RequestHandler is already initialized');
		}
		$environmentSettingsModel = $core->getEnvironmentSettingsModel();
		$coreProperties = $core->getCoreProperties();
		$sessionHandler = $core->getSessionHandler();

		$requestHandler = RequestHandler::$instance = new RequestHandler();
		$requestHandler->checkDomain($environmentSettingsModel->getAllowedDomains());
		$requestHandler->pathParts = explode(separator: '/', string: Httprequest::getPath());
		$requestHandler->countPathParts = count(value: $requestHandler->pathParts);
		$requestHandler->fileName = Sanitizer::trimmedString(input: $requestHandler->pathParts[$requestHandler->countPathParts - 1]);
		$requestHandler->defaultRoutesByLanguageCode = $requestHandler->initDefaultRoutes(allRoutes: $allRoutes, environmentSettingsModel: $environmentSettingsModel);
		$requestHandler->route = $requestHandler->initRoute(
			countPathParts: $requestHandler->countPathParts,
			allRoutes: $allRoutes,
			core: $core
		);
		if (is_null($requestHandler->route)) {
			throw new NotFoundException();
		}
		$requestHandler->initPropertiesFromRoute(
			route: $requestHandler->route,
			coreProperties: $coreProperties,
			environmentSettingsModel: $environmentSettingsModel,
			sessionHandler: $sessionHandler
		);
		$requestHandler->setFileProperties();
		if (!is_null(value: $requestHandler->acceptedExtension) && $requestHandler->fileExtension !== $requestHandler->acceptedExtension) {
			throw new NotFoundException();
		}

		return $requestHandler;
	}

	private function checkDomain(array $allowedDomains): void
	{
		$host = HttpRequest::getHost();

		if (count(value: $allowedDomains) === 0) {
			throw new Exception(message: 'Please define at least one allowed domain');
		}

		if (!in_array(needle: $host, haystack: $allowedDomains)) {
			throw new Exception(message: $host . ' is not set as allowed domain');
		}
	}

	/**
	 * @param Route[]                  $allRoutes
	 * @param EnvironmentSettingsModel $environmentSettingsModel
	 *
	 * @return Route[]
	 */
	private function initDefaultRoutes(array $allRoutes, EnvironmentSettingsModel $environmentSettingsModel): array
	{
		$defaultRoutes = [];

		foreach ($allRoutes as $route) {
			if (!$route->isDefaultForLanguage()) {
				continue;
			}
			$languageCode = $route->getLanguageCode();
			if (array_key_exists(key: $languageCode, array: $defaultRoutes)) {
				throw new LogicException(message: 'Default route for language ' . $languageCode . ' is already set');
			}
			if (array_key_exists(key: $languageCode, array: $environmentSettingsModel->getAvailableLanguages())) {
				$defaultRoutes[$languageCode] = $route;
			}
		}

		// Make sure there is at least one default route
		if (count(value: $defaultRoutes) === 0) {
			throw new LogicException(message: 'There must be at least one default route with a language that is allowed by this domain');
		}

		return $defaultRoutes;
	}

	/**
	 * @param int     $countPathParts
	 * @param Route[] $allRoutes
	 * @param Core    $core
	 *
	 * @return Route|null
	 */
	private function initRoute(int $countPathParts, array $allRoutes, Core $core): ?Route
	{
		$countDirectories = $countPathParts - 2;
		$requestedDirectories = '/';
		for ($x = 1; $x <= $countDirectories; $x++) {
			$requestedDirectories .= $this->pathParts[$x] . '/';
		}

		$requestedPath = Httprequest::getPath();
		foreach ($allRoutes as $route) {
			$routePath = $route->getPath();
			if ($routePath === $requestedDirectories) {
				return $route;
			}
			if (preg_match_all(pattern: '#\${(.*?)}#', subject: $routePath, matches: $matches1) === 0) {
				continue;
			}
			$pattern = '#^' . str_replace(search: $matches1[0], replace: '(.*)', subject: $routePath) . '$#';
			if (preg_match(
					pattern: $pattern,
					subject: $requestedPath,
					matches: $matches2
				) === 0) {
				continue;
			}
			foreach ($matches1[1] as $nr => $variableName) {
				$nr = $nr + 1;
				$val = $matches2[$nr] ?? '';
				if ($variableName === 'fileName') {
					$this->fileName = $val;
				} else if ($variableName == 'fileGroup') {
					$this->fileGroup = $val;
				} else {
					$this->routeVariables[$variableName] = $val;
				}
			}

			return $route;
		}

		if (HttpRequest::getURI() === '/') {
			$defaultRoutesByLanguageCode = $this->defaultRoutesByLanguageCode;
			$preferredLanguage = $core->getSessionHandler()->getPreferredLanguage();
			if (!is_null(value: $preferredLanguage)) {
				foreach ($defaultRoutesByLanguageCode as $languageCode => $route) {
					if ($languageCode === $preferredLanguage) {
						$core->redirect(relativeOrAbsoluteUri: $route->getPath());
					}
				}
			}

			foreach (Httprequest::getLanguages() as $languageCode) {
				if (array_key_exists(key: $languageCode, array: $defaultRoutesByLanguageCode)) {
					$core->redirect(relativeOrAbsoluteUri: $defaultRoutesByLanguageCode[$languageCode]->getPath());
				}
			}

			// Redirect to first default route if none is available in accepted languages
			$core->redirect(relativeOrAbsoluteUri: current(array: $defaultRoutesByLanguageCode));
		}

		return null;
	}

	private function initPropertiesFromRoute(
		Route                    $route,
		CoreProperties           $coreProperties,
		EnvironmentSettingsModel $environmentSettingsModel,
		AbstractSessionHandler   $sessionHandler
	): void {
		$forceFileGroup = $route->getForceFileGroup();
		if (!is_null(value: $forceFileGroup) && $forceFileGroup !== '') {
			$this->fileGroup = $forceFileGroup;
		}

		$forceFileName = $route->getForceFileName();
		if (!is_null(value: $forceFileName) && $forceFileName !== '') {
			$this->fileName = $forceFileName;
		}
		$this->viewGroup = $route->getViewGroup();
		$this->viewDirectory = $coreProperties->getSiteViewsDir() . $route->getViewGroup() . '/';
		$this->defaultFileName = $route->getDefaultFileName();
		$this->acceptedExtension = $route->getAcceptedExtension();
		$this->language = !is_null(value: $route->getLanguageCode()) ? $route->getLanguageCode() : key(array: $environmentSettingsModel->getAvailableLanguages());
		if ($sessionHandler->getPreferredLanguage() !== $this->language) {
			$sessionHandler->setPreferredLanguage(language: $this->language);
		}
		$this->contentType = $route->getContentType();
	}

	private function setFileProperties(): void
	{
		$fileName = (trim(string: $this->fileName) === '') ? $this->defaultFileName : $this->fileName;
		$dotPos = strripos(haystack: $fileName, needle: '.');
		if ($dotPos === false) {
			$length = strlen(string: $fileName);
			$fileExtension = '';
		} else {
			$length = $dotPos;
			$fileExtension = substr(string: $fileName, offset: $length + 1);
		}
		$fnArr = str_replace(
			search: '__DASH__',
			replace: '-',
			subject: explode(separator: '-', string: substr(string: $fileName, offset: 0, length: $length))
		);
		$this->fileName = $fileName;
		$this->fileTitle = $fnArr[0];
		$this->pathVars = $fnArr;
		$this->fileExtension = $fileExtension;
	}

	public function getPathParts(): array
	{
		return $this->pathParts;
	}

	public function getCountPathParts(): int
	{
		return $this->countPathParts;
	}

	public function getFileName(): string
	{
		return $this->fileName;
	}

	/**
	 * @return Route[]
	 */
	public function getDefaultRoutesByLanguageCode(): array
	{
		return $this->defaultRoutesByLanguageCode;
	}

	public function getRoute(): ?Route
	{
		return $this->route;
	}

	public function getFileGroup(): ?string
	{
		return $this->fileGroup;
	}

	public function getRouteVariables(): array
	{
		return $this->routeVariables;
	}

	public function getViewGroup(): ?string
	{
		return $this->viewGroup;
	}

	public function getViewDirectory(): ?string
	{
		return $this->viewDirectory;
	}

	public function getDefaultFileName(): string
	{
		return $this->defaultFileName;
	}

	public function getAcceptedExtension(): ?string
	{
		return $this->acceptedExtension;
	}

	public function getLanguage(): ?string
	{
		return $this->language;
	}

	public function getContentType(): ?string
	{
		return $this->contentType;
	}

	public function getFileTitle(): ?string
	{
		return $this->fileTitle;
	}

	public function getPathVars(): ?array
	{
		return $this->pathVars;
	}

	public function getFileExtension(): ?string
	{
		return $this->fileExtension;
	}
}