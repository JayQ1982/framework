<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\security;

use framework\core\HttpRequest;

class CspPolicySettingsModel
{
	private const PROTOCOL_PLACEHOLDER = '{PROTOCOL}';
	private const HOST_PLACEHOLDER = '{HOST}';

	// Content Security Policy Reference: https://content-security-policy.com/
	private string $defaultSrc;
	private string $styleSrc;
	private string $fontSrc;
	private string $imgSrc;
	private string $objectSrc;
	private string $scriptSrc;
	private string $connectSrc;
	private string $baseUri;
	private string $frameSrc;
	private string $frameAncestors;

	public function __construct(
		string $defaultSrc = "'self' data: " . CspPolicySettingsModel::PROTOCOL_PLACEHOLDER . "://" . CspPolicySettingsModel::HOST_PLACEHOLDER,
		string $styleSrc = "'self'",
		string $fontSrc = "'self'",
		string $imgSrc = "'self'",
		string $objectSrc = "'none'",
		string $scriptSrc = "'strict-dynamic'",
		string $connectSrc = "'none'",
		string $baseUri = "'self'",
		string $frameSrc = "'none'",
		string $frameAncestors = "'none'",
	) {
		$httpRequest = HttpRequest::getInstance();
		$this->defaultSrc = str_replace(
			search: [CspPolicySettingsModel::PROTOCOL_PLACEHOLDER, CspPolicySettingsModel::HOST_PLACEHOLDER],
			replace: [$httpRequest->getProtocol(), $httpRequest->getHost()],
			subject: $defaultSrc
		);
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

		if ($this->defaultSrc !== '') {
			$dataArray[] = 'default-src ' . $this->defaultSrc;
		}
		if ($this->styleSrc !== '') {
			$dataArray[] = 'style-src ' . $this->styleSrc;
		}
		if ($this->fontSrc !== '') {
			$dataArray[] = 'font-src ' . $this->fontSrc;
		}
		if ($this->imgSrc !== '') {
			$dataArray[] = 'img-src ' . $this->imgSrc;
		}
		if ($this->objectSrc !== '') {
			$dataArray[] = 'object-src ' . $this->objectSrc;
		}
		if ($this->scriptSrc !== '') {
			$val = $this->scriptSrc;
			if (!str_contains($val, "'none'")) {
				$val .= " 'nonce-" . $nonce . "'";
			}
			$dataArray[] = 'script-src ' . $val;
		}
		if ($this->connectSrc !== '') {
			$dataArray[] = 'connect-src ' . $this->connectSrc;
		}
		if ($this->baseUri !== '') {
			$dataArray[] = 'base-uri ' . $this->baseUri;
		}
		if ($this->frameSrc !== '') {
			$dataArray[] = 'frame-src ' . $this->frameSrc;
		}
		if ($this->frameAncestors !== '') {
			$dataArray[] = 'frame-ancestors ' . $this->frameAncestors;
		}

		return empty($dataArray) ? '' : implode('; ', $dataArray) . ';';
	}
}