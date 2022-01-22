<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\phone;

use Exception;

/**
 * Adapted from https://github.com/google/libphonenumber
 */
class PhoneParseException extends Exception
{
	public const EMPTY_STRING = 0;
	public const INVALID_COUNTRY_CODE = 1;
	public const NOT_A_NUMBER = 2;
	public const TOO_SHORT_AFTER_IDD = 3;
	public const TOO_SHORT_NSN = 4;
	public const TOO_LONG = 5;
}