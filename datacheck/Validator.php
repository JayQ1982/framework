<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\datacheck;

use framework\datacheck\validatorTypes\DomainValidator;
use framework\datacheck\validatorTypes\IpTypeEnum;
use framework\datacheck\validatorTypes\IpValidator;
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
		return IpValidator::validate(input: $input, ipType: IpTypeEnum::ip);
	}

	public static function ipv4(mixed $input): bool
	{
		return IpValidator::validate($input, ipType: IpTypeEnum::ipv4);
	}

	public static function ipv6(mixed $input): bool
	{
		return IpValidator::validate($input, ipType: IpTypeEnum::ipv6);
	}
}