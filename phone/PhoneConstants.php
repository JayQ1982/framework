<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

namespace framework\phone;

class PhoneConstants
{
	public const DOUBLE_ZERO = '00';
	public const PLUS_SIGN = '+';
	public const MAX_INPUT_STRING_LENGTH = 250;
	public const RFC3966_ISDN_SUBADDRESS = ';isub=';
	public const PLUS_CHARS = '+＋';
	public const DIGITS = "\\p{Nd}";
	public const RFC3966_PHONE_CONTEXT = ';phone-context=';
	public const RFC3966_PREFIX = 'tel:';
	public const MIN_LENGTH_FOR_NSN = 2;
	public const MAX_LENGTH_FOR_NSN = 17;
	public const VALID_PUNCTUATION = "-x\xE2\x80\x90-\xE2\x80\x95\xE2\x88\x92\xE3\x83\xBC\xEF\xBC\x8D-\xEF\xBC\x8F \xC2\xA0\xC2\xAD\xE2\x80\x8B\xE2\x81\xA0\xE3\x80\x80()\xEF\xBC\x88\xEF\xBC\x89\xEF\xBC\xBB\xEF\xBC\xBD.\\[\\]/~\xE2\x81\x93\xE2\x88\xBC";
	public const STAR_SIGN = '*';
	public const VALID_ALPHA = 'A-Za-z';
	public const RFC3966_EXTN_PREFIX = ';ext=';
	public const CAPTURING_EXTN_DIGITS = '(' . PhoneConstants::DIGITS . '{1,7})';
	public const REGEX_FLAGS = 'ui'; //Unicode and case-insensitive
	public const VALID_PHONE_NUMBER = '[' . PhoneConstants::PLUS_CHARS . ']*(?:[' . PhoneConstants::VALID_PUNCTUATION . PhoneConstants::STAR_SIGN . ']*[' . PhoneConstants::DIGITS . ']){3,}[' . PhoneConstants::VALID_PUNCTUATION . PhoneConstants::STAR_SIGN . PhoneConstants::VALID_ALPHA . PhoneConstants::DIGITS . ']*';

	// The country_code is derived based on a phone number with a leading "+", e.g. the French number "+33 1 42 68 53 00".
	public const FROM_NUMBER_WITH_PLUS_SIGN = 0;
	// The country_code is derived based on a phone number with a leading IDD, e.g. the French number "011 33 1 42 68 53 00", as it is dialled from US.
	public const FROM_NUMBER_WITH_IDD = 1;
	/**
	 * The country_code is derived NOT based on the phone number itself, but from the defaultCountry parameter provided in the parsing function by the clients.
	 * This happens mostly for numbers written in the national format (without country code).
	 * For example, this would be set when parsing the French number "01 42 68 53 00", when defaultCountry is supplied as France.
	 */
	public const FROM_DEFAULT_COUNTRY = 3;

	public const ALPHA_MAPPINGS = [
		'A' => '2',
		'B' => '2',
		'C' => '2',
		'D' => '3',
		'E' => '3',
		'F' => '3',
		'G' => '4',
		'H' => '4',
		'I' => '4',
		'J' => '5',
		'K' => '5',
		'L' => '5',
		'M' => '6',
		'N' => '6',
		'O' => '6',
		'P' => '7',
		'Q' => '7',
		'R' => '7',
		'S' => '7',
		'T' => '8',
		'U' => '8',
		'V' => '8',
		'W' => '9',
		'X' => '9',
		'Y' => '9',
		'Z' => '9',
	];
	public const ASCII_DIGIT_MAPPINGS = [
		'0' => '0',
		'1' => '1',
		'2' => '2',
		'3' => '3',
		'4' => '4',
		'5' => '5',
		'6' => '6',
		'7' => '7',
		'8' => '8',
		'9' => '9',
	];
	public const ALPHA_PHONE_MAPPINGS = PhoneConstants::ALPHA_MAPPINGS + PhoneConstants::ASCII_DIGIT_MAPPINGS;
	public const NUMERIC_CHARACTERS = [
		"\xef\xbc\x90" => 0,
		"\xef\xbc\x91" => 1,
		"\xef\xbc\x92" => 2,
		"\xef\xbc\x93" => 3,
		"\xef\xbc\x94" => 4,
		"\xef\xbc\x95" => 5,
		"\xef\xbc\x96" => 6,
		"\xef\xbc\x97" => 7,
		"\xef\xbc\x98" => 8,
		"\xef\xbc\x99" => 9,

		"\xd9\xa0" => 0,
		"\xd9\xa1" => 1,
		"\xd9\xa2" => 2,
		"\xd9\xa3" => 3,
		"\xd9\xa4" => 4,
		"\xd9\xa5" => 5,
		"\xd9\xa6" => 6,
		"\xd9\xa7" => 7,
		"\xd9\xa8" => 8,
		"\xd9\xa9" => 9,

		"\xdb\xb0" => 0,
		"\xdb\xb1" => 1,
		"\xdb\xb2" => 2,
		"\xdb\xb3" => 3,
		"\xdb\xb4" => 4,
		"\xdb\xb5" => 5,
		"\xdb\xb6" => 6,
		"\xdb\xb7" => 7,
		"\xdb\xb8" => 8,
		"\xdb\xb9" => 9,

		"\xe1\xa0\x90" => 0,
		"\xe1\xa0\x91" => 1,
		"\xe1\xa0\x92" => 2,
		"\xe1\xa0\x93" => 3,
		"\xe1\xa0\x94" => 4,
		"\xe1\xa0\x95" => 5,
		"\xe1\xa0\x96" => 6,
		"\xe1\xa0\x97" => 7,
		"\xe1\xa0\x98" => 8,
		"\xe1\xa0\x99" => 9,
	];
	public const MAX_LENGTH_COUNTRY_CODE = 3;
	public const DEFAULT_EXTN_PREFIX = ' ext. ';
}