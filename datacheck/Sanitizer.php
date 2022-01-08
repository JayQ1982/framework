<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\datacheck;

use framework\datacheck\sanitizerTypes\DomainSanitizer;
use framework\datacheck\sanitizerTypes\FloatSanitizer;
use framework\datacheck\sanitizerTypes\IntegerSanitizer;

/**
 * Class "Sanitizer" is a "helper class"
 */
class Sanitizer
{
	public static function domain($input): string
	{
		return DomainSanitizer::sanitize($input);
	}

	public static function trimmedString(null|string|float|int|bool $input): string
	{
		if (is_null($input)) {
			return '';
		}

		return trim($input);
	}

	public static function integer($input): int
	{
		return IntegerSanitizer::sanitize($input);
	}

	public static function float($input): float
	{
		return FloatSanitizer::sanitize($input);
	}
}