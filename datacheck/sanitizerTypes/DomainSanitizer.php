<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\datacheck\sanitizerTypes;

use RuntimeException;

final class DomainSanitizer
{
	public static function sanitize(string $input): string
	{
		$domain = mb_strtolower(string: trim(string: $input));
		if ($domain === '') {
			return '';
		}
		$sanitizedDomain = preg_replace(
			pattern: [
				'/\xE2\x80\x8B/',
				'@^[a-z]+://@i',
				'@^www\.@i',
				'/&#8203;/',
				'/\?/',
				'/ /',
				'/\/$/',
			],
			replacement: '',
			subject: $domain
		);
		if (
			str_contains(haystack: $domain, needle: 'www.')
			&& count(value: explode(separator: '.', string: $sanitizedDomain)) === 1
		) {
			$sanitizedDomain = 'www.' . $sanitizedDomain;
		}
		if (is_null(value: $sanitizedDomain)) {
			throw new RuntimeException(message: 'Domain value is not valid: "' . $domain . '"');
		}

		return $sanitizedDomain;
	}
}