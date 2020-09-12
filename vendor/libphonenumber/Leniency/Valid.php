<?php

namespace framework\vendor\libphonenumber\Leniency;

use framework\vendor\libphonenumber\PhoneNumber;
use framework\vendor\libphonenumber\PhoneNumberMatcher;
use framework\vendor\libphonenumber\PhoneNumberUtil;

class Valid extends AbstractLeniency
{
	protected static $level = 2;

	/**
	 * Phone numbers accepted are PhoneNumberUtil::isPossibleNumber() and PhoneNumberUtil::isValidNumber().
	 * Numbers written in national format must have their national-prefix present if it is usually written
	 * for a number of this type.
	 *
	 * @param PhoneNumber     $number
	 * @param string          $candidate
	 * @param PhoneNumberUtil $util
	 *
	 * @return bool
	 */
	public static function verify(PhoneNumber $number, $candidate, PhoneNumberUtil $util): bool
	{
		if (!$util->isValidNumber($number)
			|| !PhoneNumberMatcher::containsOnlyValidXChars($number, $candidate, $util)) {
			return false;
		}

		return PhoneNumberMatcher::isNationalPrefixPresentIfRequired($number, $util);
	}
}
