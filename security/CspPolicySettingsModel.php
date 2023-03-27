<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\security;

use framework\core\HttpRequest;

readonly class CspPolicySettingsModel
{
	public const PROTOCOL_PLACEHOLDER = '{PROTOCOL}';
	public const HOST_PLACEHOLDER = '{HOST}';

	// Content Security Policy Reference: https://content-security-policy.com/
	private string $defaultSrc;

	public function __construct(
		string         $defaultSrc = "'self' data: " . CspPolicySettingsModel::PROTOCOL_PLACEHOLDER . "://" . CspPolicySettingsModel::HOST_PLACEHOLDER,
		private string $styleSrc = "'self'",
		private string $fontSrc = "'self'",
		private string $imgSrc = "'self'",
		private string $objectSrc = "'none'",
		private string $scriptSrc = "'strict-dynamic'",
		private string $connectSrc = "'none'",
		private string $baseUri = "'self'",
		private string $frameSrc = "'none'",
		private string $frameAncestors = "'none'",
	) {
		$this->defaultSrc = str_replace(
			search: [
				CspPolicySettingsModel::PROTOCOL_PLACEHOLDER,
				CspPolicySettingsModel::HOST_PLACEHOLDER,
			],
			replace: [
				HttpRequest::getProtocol(),
				HttpRequest::getHost(),
			],
			subject: $defaultSrc
		);
	}

	public function getHttpHeaderDataString(string $nonce): string
	{
		$dataArray = [];

		if ($this->defaultSrc !== '') {
			$dataArray[] = 'default-src ' . $this->defaultSrc;
		}
		if ($this->styleSrc !== '') {
			$val = $this->styleSrc;
			if (!str_contains(haystack: $val, needle: "'none'") && !str_contains(haystack: $val, needle: "'unsafe-inline'")) {
				$val .= " 'nonce-" . $nonce . "'";
			}
			$dataArray[] = 'style-src ' . $val;
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
			if (!str_contains(haystack: $val, needle: "'none'") && !str_contains(haystack: $val, needle: "'unsafe-inline'")) {
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

		return empty($dataArray) ? '' : implode(separator: '; ', array: $dataArray) . ';';
	}
}