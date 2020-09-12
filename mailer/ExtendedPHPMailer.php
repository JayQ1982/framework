<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\mailer;

use framework\vendor\PHPMailer\PHPMailer;

class ExtendedPHPMailer extends PHPMailer
{
	public static function validateAddress($address, $patternselect = null): bool
	{
		//Reject line breaks in addresses; it's valid RFC5322, but not RFC5321
		if (strpos($address, "\n") !== false || strpos($address, "\r") !== false) {
			return false;
		}

		return (bool)filter_var($address, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
	}
}
/* EOF */