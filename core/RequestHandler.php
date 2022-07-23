<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use Exception;
use framework\exception\NotFoundException;
use framework\session\AbstractSessionHandler;
use LogicException;

class RequestHandler
{
	private static ?RequestHandler $instance = null;

	public readonly array $pathParts;
	public readonly int $countPathParts;
	private string $fileName;
	public readonly RouteCollection $defaultRoutesByLanguage;
	public readonly Route $route;
	private ?string $fileGroup = null;
	private array $routeVariables = [];
	public readonly Language $language;
	public readonly string $fileTitle;
	public readonly array $pathVars;
	public readonly string $fileExtension;

	public static function get(): RequestHandler
	{
		return RequestHandler::$instance;
	}

	public static function register(RouteCollection $routeCollection): void
	{
		new RequestHandler(allRoutes: $routeCollection);
	}

	private function __construct(RouteCollection $allRoutes)
	{
		if (!is_null(value: RequestHandler::$instance)) {
			throw new LogicException(message: 'RequestHandler is already registered');
		}
		RequestHandler::$instance = $this;
		$environmentSettingsModel = EnvironmentSettingsModel::get();
		$this->checkDomain(allowedDomains: $environmentSettingsModel->allowedDomains);
		$this->pathParts = explode(separator: '/', string: HttpRequest::getPath());
		$this->countPathParts = count(value: $this->pathParts);
		$this->fileName = trim(string: $this->pathParts[$this->countPathParts - 1]);
		$this->defaultRoutesByLanguage = $this->initDefaultRoutes(allRoutes: $allRoutes, environmentSettingsModel: $environmentSettingsModel);
		$this->route = $this->initRoute(countPathParts: $this->countPathParts, allRoutes: $allRoutes);
		$forceFileGroup = $this->route->forceFileGroup;
		if (!is_null(value: $forceFileGroup) && $forceFileGroup !== '') {
			$this->fileGroup = $forceFileGroup;
		}
		$forceFileName = $this->route->forceFileName;
		if (!is_null(value: $forceFileName) && $forceFileName !== '') {
			$this->fileName = $forceFileName;
		}
		$this->language = !is_null(value: $this->route->language) ? $this->route->language : $environmentSettingsModel->availableLanguages->getFirstLanguage();
		$sessionHandler = AbstractSessionHandler::getSessionHandler();
		$preferredLanguageCode = $sessionHandler->getPreferredLanguageCode();
		if (is_null(value: $preferredLanguageCode) || $preferredLanguageCode !== $this->language->code) {
			$sessionHandler->setPreferredLanguage(language: $this->language);
		}
		$fileName = (trim(string: $this->fileName) === '') ? $this->route->defaultFileName : $this->fileName;
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
		if (!is_null(value: $this->route->acceptedExtension) && $this->fileExtension !== $this->route->acceptedExtension) {
			throw new NotFoundException();
		}
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

	private function initDefaultRoutes(RouteCollection $allRoutes, EnvironmentSettingsModel $environmentSettingsModel): RouteCollection
	{
		$defaultRoutes = new RouteCollection();
		$usedLanguages = new LanguageCollection();
		foreach ($allRoutes->getRoutes() as $route) {
			if (!$route->isDefaultForLanguage) {
				continue;
			}
			if ($usedLanguages->hasLanguage(languageCode: $route->language->code)) {
				throw new LogicException(message: 'Default route for language ' . $route->language->code . ' is already set');
			}
			if ($environmentSettingsModel->availableLanguages->hasLanguage(languageCode: $route->language->code)) {
				$defaultRoutes->addRoute(route: $route);
				$usedLanguages->add(language: $route->language);
			}
		}

		// Make sure there is at least one default route
		if (!$defaultRoutes->hasRoutes()) {
			throw new LogicException(message: 'There must be at least one default route with a language that is allowed by this domain');
		}

		return $defaultRoutes;
	}

	private function initRoute(int $countPathParts, RouteCollection $allRoutes): Route
	{
		$countDirectories = $countPathParts - 2;
		$requestedDirectories = '/';
		for ($x = 1; $x <= $countDirectories; $x++) {
			$requestedDirectories .= $this->pathParts[$x] . '/';
		}

		$requestedPath = HttpRequest::getPath();
		foreach ($allRoutes->getRoutes() as $route) {
			$routePath = $route->path;
			if ($routePath === $requestedDirectories) {
				return $route;
			}
			if (preg_match_all(
					pattern: '#\${(.*?)}#',
					subject: $routePath,
					matches: $matches1
				) === 0) {
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
				} else if ($variableName === 'fileGroup') {
					$this->fileGroup = $val;
				} else {
					$this->routeVariables[$variableName] = $val;
				}
			}

			return $route;
		}
		if (HttpRequest::getURI() === '/') {
			$defaultRoutesByLanguage = $this->defaultRoutesByLanguage;
			$preferredLanguageCode = AbstractSessionHandler::getSessionHandler()->getPreferredLanguageCode();
			if (!is_null(value: $preferredLanguageCode)) {
				foreach ($defaultRoutesByLanguage->getRoutes() as $route) {
					if ($route->language->code === $preferredLanguageCode) {
						HttpResponse::redirectAndExit(relativeOrAbsoluteUri: $route->path);
					}
				}
			}
			foreach (Httprequest::listBrowserLanguagesByQuality() as $languageCode) {
				$routeForLanguage = $defaultRoutesByLanguage->getRouteForLanguage(languageCode: $languageCode);
				if (!is_null(value: $routeForLanguage)) {
					HttpResponse::redirectAndExit(relativeOrAbsoluteUri: $routeForLanguage->path);
				}
			}
			// Redirect to first default route if none is available in accepted languages
			HttpResponse::redirectAndExit(relativeOrAbsoluteUri: $defaultRoutesByLanguage->getFirstRoute()->path);
		}

		throw new NotFoundException();
	}

	public function getFileName(): string
	{
		return $this->fileName;
	}

	public function getFileGroup(): ?string
	{
		return $this->fileGroup;
	}

	public function getRouteVariables(): array
	{
		return $this->routeVariables;
	}
}