<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\datacheck\validatorTypes;

use framework\common\StringUtils;
use framework\datacheck\Validator;

class DomainValidator
{
	public static function validate(string $input): bool
	{
		if (!Validator::stringWithoutWhitespaces($input)) {
			return false;
		}
		if (!str_contains($input, '.')) {
			return false;
		}
		// Domainname + '.' + TLD = minimum 5 characters
		if (mb_strlen($input) < 5) {
			return false;
		}
		$encodedData = StringUtils::utf8_to_punycode($input);
		if ($encodedData === false) {
			return false;
		}
		if (filter_var(value: 'http://' . $encodedData, filter: FILTER_VALIDATE_URL) === false) {
			return false;
		}
		$pieces = explode('.', $input);
		$realTld = array_pop($pieces);
		if (!TldValidator::validate($realTld)) {
			return false;
		}
		foreach ($pieces as $fragment) {
			if (strlen($fragment) > 64) {
				return false;
			}
			if (str_starts_with($fragment, '-') || str_ends_with($fragment, '-')) {
				return false;
			}
			if (substr_count($fragment, '--') > 1) {
				return false;
			}
		}

		return true;
	}
}