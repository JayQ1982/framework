<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\security;

class CspPolicySettingsModel
{
	private ?string $defaultSrc;
	private ?string $styleSrc;
	private ?string $fontSrc;
	private ?string $imgSrc;
	private ?string $objectSrc;
	private ?string $scriptSrc;
	private ?string $connectSrc;
	private ?string $baseUri;
	private ?string $frameSrc;
	private ?string $frameAncestors;

	public function __construct(
		?string $defaultSrc = null,
		?string $styleSrc = null,
		?string $fontSrc = null,
		?string $imgSrc = null,
		?string $objectSrc = null,
		?string $scriptSrc = null,
		?string $connectSrc = null,
		?string $baseUri = null,
		?string $frameSrc = null,
		?string $frameAncestors = null,
	) {
		$this->defaultSrc = $defaultSrc;
		$this->styleSrc = $styleSrc;
		$this->fontSrc = $fontSrc;
		$this->imgSrc = $imgSrc;
		$this->objectSrc = $objectSrc;
		$this->scriptSrc = $scriptSrc;
		$this->connectSrc = $connectSrc;
		$this->baseUri = $baseUri;
		$this->frameSrc = $frameSrc;
		$this->frameAncestors = $frameAncestors;
	}

	public function getHttpHeaderDataString(string $nonce): string
	{
		$dataArray = [];

		if (!is_null($this->defaultSrc)) {
			$dataArray[] = 'default-src ' . $this->defaultSrc;
		}
		if (!is_null($this->styleSrc)) {
			$dataArray[] = 'style-src ' . $this->styleSrc;
		}
		if (!is_null($this->fontSrc)) {
			$dataArray[] = 'font-src ' . $this->fontSrc;
		}
		if (!is_null($this->imgSrc)) {
			$dataArray[] = 'img-src ' . $this->imgSrc;
		}
		if (!is_null($this->objectSrc)) {
			$dataArray[] = 'object-src ' . $this->objectSrc;
		}
		if (!is_null($this->scriptSrc)) {
			$val = $this->scriptSrc;
			if (!str_contains($val, "'none'")) {
				$val .= " 'nonce-" . $nonce . "'";
			}
			$dataArray[] = 'script-src ' . $val;
		}
		if (!is_null($this->connectSrc)) {
			$dataArray[] = 'connect-src ' . $this->connectSrc;
		}
		if (!is_null($this->baseUri)) {
			$dataArray[] = 'base-uri ' . $this->baseUri;
		}
		if (!is_null($this->frameSrc)) {
			$dataArray[] = 'frame-src ' . $this->frameSrc;
		}
		if (!is_null($this->frameAncestors)) {
			$dataArray[] = 'frame-ancestors ' . $this->frameAncestors;
		}

		return empty($dataArray) ? '' : implode('; ', $dataArray) . ';';
	}
}
