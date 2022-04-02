<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

namespace framework\phone;

use Exception;

class PhoneParseException extends Exception
{
	public const EMPTY_STRING = 0;
	public const INVALID_COUNTRY_CODE = 1;
	public const NOT_A_NUMBER = 2;
	public const TOO_SHORT_AFTER_IDD = 3;
	public const TOO_SHORT_NSN = 4;
	public const TOO_LONG = 5;
}