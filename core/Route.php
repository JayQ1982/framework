<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use framework\Core;
use LogicException;

class Route
{
	/** @var Route[] */
	private static array $routesByPath = [];
	public readonly string $viewDirectory;

	public function __construct(
		public readonly string      $path,
		public readonly string      $viewGroup,
		public readonly string      $defaultFileName,
		public readonly bool        $isDefaultForLanguage,
		public readonly ContentType $defaultContentType,
		public readonly ?Language   $language = null,
		public readonly ?string     $acceptedExtension = ContentType::HTML,
		public readonly ?string     $forceFileGroup = null,
		public readonly ?string     $forceFileName = null
	) {
		if (array_key_exists(key: $path, array: Route::$routesByPath)) {
			throw new LogicException(message: 'There is already a route with this path: ' . $path);
		}
		Route::$routesByPath[$path] = $this;
		$this->viewDirectory = Core::get()->viewDirectory . $this->viewGroup . DIRECTORY_SEPARATOR;
	}
}