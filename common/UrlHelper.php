<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

use framework\core\HttpRequest;

class UrlHelper
{
	public static function generateAbsoluteUri(string $relativeOrAbsoluteUri): string
	{
		$components = parse_url(url: $relativeOrAbsoluteUri);
		if (!array_key_exists(key: 'host', array: $components)) {
			if (str_starts_with(haystack: $relativeOrAbsoluteUri, needle: '/')) {
				$directory = '';
			} else {
				$directory = dirname(path: HttpRequest::getURI());
				$directory = ($directory === '/' || $directory === '\\') ? '/' : $directory . '/';
			}
			$absoluteUri = Httprequest::getProtocol() . '://' . HttpRequest::getHost() . $directory . $relativeOrAbsoluteUri;
		} else {
			$absoluteUri = $relativeOrAbsoluteUri;
		}
		if (defined(constant_name: 'SID') && SID !== '') {
			$absoluteUri .= ((preg_match(pattern: '/(.*)\?(.+)/', subject: $absoluteUri)) ? '&' : '?') . SID;
		}

		return $absoluteUri;
	}
}