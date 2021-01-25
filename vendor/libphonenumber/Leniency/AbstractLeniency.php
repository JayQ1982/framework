<?php

namespace framework\vendor\libphonenumber\Leniency;

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
