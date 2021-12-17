<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\datacheck\sanitizerTypes;

use framework\datacheck\Sanitizer;
use RuntimeException;

final class IntegerSanitizer
{
	public static function sanitize(int|float|string $input): int
	{
		if (is_int($input)) {
			return $input;
		}
		if (!is_numeric($input)) {
			throw new RuntimeException('Value is not suitable as INT.');
		}
		if (is_string($input)) {
			$input = Sanitizer::trimmedString(input: $input);
			// An INT contains only digits, but might have an - in front of it:
			if (preg_match('/^-?\d*$/', $input) === 1) {
				// It might be "too big":
				if (bccomp($input, (string)PHP_INT_MAX) === 1 || bccomp($input, (string)PHP_INT_MIN) === -1) {
					throw new RuntimeException('Value is out of range as INT.');
				}

				return intval($input);
			}
			// Maybe it's a "stringed FLOAT"?
			try {
				$input = FloatSanitizer::sanitize($input);
			} catch (RuntimeException) {
				throw new RuntimeException('Value is not suitable as INT.');
			}
		}

		// it's a float
		if ($input > PHP_INT_MAX || $input < PHP_INT_MIN) {
			throw new RuntimeException('Value is out of range as INT.');
		}
		if (fmod($input, 1.0) !== 0.0) {
			throw new RuntimeException('Value is not a whole number.');
		}

		return intval($input);
	}
}