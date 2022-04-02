<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

class TimeUtils
{
	public static function checkDateFormat(string $format): string
	{
		// Check for Windows to find and replace the %e modifier correctly
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$format = preg_replace(
				pattern: '#(?<!%)((?:%%)*)%e#',
				replacement: '\1%#d',
				subject: $format
			);
		}

		return str_replace('%T', '%H:%i:%s', $format);
	}
}