<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use LogicException;

class Route
{
	private static array $paths = [];

	public function __construct(
		private string  $path,
		private string  $viewGroup,
		private string  $defaultFileName,
		private bool    $isDefaultForLanguage,
		private ?string $contentType = null,
		private ?string $languageCode = null,
		private ?string $acceptedExtension = HttpResponse::TYPE_HTML,
		private ?string $forceFileGroup = null,
		private ?string $forceFileName = null
	) {
		if (in_array(needle: $path, haystack: Route::$paths)) {
			throw new LogicException(message: 'There is already a route with this path: ' . $path);
		}
		Route::$paths[] = $path;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getViewGroup(): string
	{
		return $this->viewGroup;
	}

	public function getAcceptedExtension(): ?string
	{
		return $this->acceptedExtension;
	}

	public function getDefaultFileName(): string
	{
		return $this->defaultFileName;
	}

	public function getLanguageCode(): string
	{
		return $this->languageCode;
	}

	public function getContentType(): string
	{
		return $this->contentType;
	}

	public function isDefaultForLanguage(): bool
	{
		return $this->isDefaultForLanguage;
	}

	public function getForceFileGroup(): ?string
	{
		return $this->forceFileGroup;
	}

	public function getForceFileName(): ?string
	{
		return $this->forceFileName;
	}
}