<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\datacheck\sanitizerTypes;

use framework\datacheck\Sanitizer;
use RuntimeException;

final class DomainSanitizer
{
	public static function sanitize(string $input): string
	{
		$domain = mb_strtolower(Sanitizer::trimmedString(input: $input));
		if ($domain === '') {
			return '';
		}
		$rplArr = ['/\xE2\x80\x8B/', '@^[a-z]+://@i', '@^www\.@i', '/&#8203;/', '/\?/', '/ /', '/\/$/'];

		$sanitizedDomain = preg_replace(
			pattern: $rplArr,
			replacement: '',
			subject: $domain
		);
		if (is_null($sanitizedDomain)) {
			throw new RuntimeException('Domain value is not valid: "' . $domain . '"');
		}

		return $sanitizedDomain;
	}
}