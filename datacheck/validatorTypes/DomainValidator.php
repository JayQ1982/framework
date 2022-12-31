<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\datacheck\validatorTypes;

use framework\datacheck\Validator;

class DomainValidator
{
	public static function validate(string $input): bool
	{
		if (!Validator::stringWithoutWhitespaces(input: $input)) {
			return false;
		}
		$pieces = explode(separator: '.', string: $input);
		if ($pieces < 2) {
			return false;
		}
		// Domainname + '.' + TLD = minimum 5 characters
		if (mb_strlen(string: $input) < 5) {
			return false;
		}
		$realTld = array_pop($pieces);
		if (!TldValidator::validate(input: $realTld)) {
			return false;
		}
		$encodedData = idn_to_ascii(domain: $input);
		if ($encodedData === false) {
			return false;
		}
		if (filter_var(value: 'https://' . $encodedData, filter: FILTER_VALIDATE_URL) === false) {
			return false;
		}

		return true;
	}
}