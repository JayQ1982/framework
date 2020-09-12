<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use DateTime;

class FrameworkUtils
{
	public static function castTypeToString($value)
	{
		if (is_null($value)) {
			return null;
		}

		if (is_object($value)) {
			if ($value instanceof DateTime) {
				return $value->format('Y-m-d H:i:s');
			} else {
				return (string)$value;
			}
		}

		return $value;
	}
}
/* EOF */