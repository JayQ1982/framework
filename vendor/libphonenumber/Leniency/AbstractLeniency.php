<?php

namespace framework\vendor\libphonenumber\Leniency;

use BadMethodCallException;
use framework\vendor\libphonenumber\PhoneNumber;
use framework\vendor\libphonenumber\PhoneNumberUtil;
use RuntimeException;

abstract class AbstractLeniency
{
	/**
	 * Integer level to compare 'ENUMs'
	 *
	 * @var int
	 */
	protected static $level;

	/**
	 * Returns true if $number is a verified number according to this leniency
	 *
	 * @param PhoneNumber     $number
	 * @param string          $candidate
	 * @param PhoneNumberUtil $util
	 *
	 * @return bool
	 * @codeCoverageIgnore
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function verify(PhoneNumber $number, $candidate, PhoneNumberUtil $util): bool
	{
		// This can not be called directly
		throw new BadMethodCallException;
	}

	/**
	 * Compare against another Leniency
	 *
	 * @param AbstractLeniency $leniency
	 *
	 * @return int
	 */
	public static function compareTo(AbstractLeniency $leniency)
	{
		return static::getLevel() - $leniency::getLevel();
	}

	protected static function getLevel()
	{
		if (static::$level === null) {
			throw new RuntimeException('$level should be defined');
		}

		return static::$level;
	}

	public function __toString()
	{
		return str_replace('libphonenumber\\Leniency\\', '', get_class($this));
	}
}
