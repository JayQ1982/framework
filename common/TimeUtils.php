<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\common;

class TimeUtils
{
	public static function checkDateFormat(string $format): string
	{
		// Check for Windows to find and replace the %e modifier correctly
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
		}

		return str_replace('%T', '%H:%i:%s', $format);
	}
}
/* EOF */