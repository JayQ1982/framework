<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\datacheck\sanitizerTypes;

use framework\datacheck\Sanitizer;
use RuntimeException;

final class FloatSanitizer
{
	public static function sanitize(float|int|string $input): float
	{
		if (is_float($input)) {
			return $input;
		}
		if (is_int($input)) {
			return (float)$input;
		}
		// It is a STRING
		$input = Sanitizer::trimmedString(input: $input);
		$hasDot = (str_contains($input, '.'));
		$hasComma = (str_contains($input, ','));
		$hasExponent = (str_contains(strtoupper($input), 'E'));

		if (!$hasDot && !$hasExponent && !$hasComma) {
			// Looks like a "stringed INT", but that "INT" might get as big as it wants.
			// An INT contains only digits, but might have an - in front of it:
			if (preg_match('/^-?\d*$/', $input) === 1) {
				// Detect value being effective 0, because this is also an indicator for a type-casting error:
				if (preg_match('/^-?0*$/', $input[0]) === 1) {
					return 0.0;
				}
				$value = floatval($input);
				if ($value == 0) { // Yes, '==' is correct
					throw new RuntimeException('Value is not suitable as FLOAT.');
				}

				return $value;
			}
			throw new RuntimeException('Value is not suitable as FLOAT.');
		}
		// It might be a "stringed FLOAT". This can get very nasty now,
		//   because result depends on LOCALE setting, and a STRING could
		//   contain anything
		// Only zero or one "E" is allowed:
		$parts = explode('E', strtoupper($input));
		if (count($parts) > 2) {
			throw new RuntimeException('Value is not suitable as FLOAT.');
		}
		// The exponent *must* be an INT, if present
		if (isset($parts[1])) {
			if (preg_match('/^-?\d*$/', $parts[1]) !== 1) {
				throw new RuntimeException('Value is not suitable as FLOAT.');
			}
		}
		// The "base" value may have one dot OR one comma
		if (preg_match('/^-?\d*[.,]?\d*$/', $parts[0]) !== 1) {
			throw new RuntimeException('Value is not suitable as FLOAT.');
		}
		// Detect value being effective 0, because this is also an indicator for a type-casting error:
		if (preg_match('/^-?0*[.,]?0*$/', $parts[0]) === 1) {
			return 0.0;
		}
		// Convert it to "international format":
		$input = str_replace(',', '.', $input);
		$value = floatval($input);
		if ($value == 0) { // Yes, '==' is correct
			throw new RuntimeException('Value is not suitable as FLOAT.');
		}

		return $value;
	}
}