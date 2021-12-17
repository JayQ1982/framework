<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\datacheck;

use framework\datacheck\validatorTypes\DomainValidator;
use framework\datacheck\validatorTypes\TldValidator;

/**
 * Class "Validator" is a "helper class"
 */
class Validator
{
	public static function stringWithoutWhitespaces(string $input): bool
	{
		return (preg_match('#\s#', $input) === 0);
	}

	public static function domain(string $input): bool
	{
		return DomainValidator::validate($input);
	}

	public static function tld(string $input): bool
	{
		return TldValidator::validate($input);
	}

	public static function ip(string $input): bool
	{
		return (filter_var($input, FILTER_VALIDATE_IP, ['flags' => null]) !== false);
	}
}