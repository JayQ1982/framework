<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\response;

use Exception;

abstract class responseContent
{
	protected string $contentType;
	private ?string $content = null;

	protected function __construct(array $allowedContentTypes, string $contentType)
	{
		if (!in_array($contentType, $allowedContentTypes)) {
			throw new Exception('Invalid contentType: ' . $contentType . ' (Allowed: ' . implode(',', $allowedContentTypes) . ')');
		}
		$this->contentType = $contentType;
	}

	protected function setContent(string $content): void
	{
		$this->content = $content;
	}

	public function getContent(): ?string
	{
		return $this->content;
	}
}