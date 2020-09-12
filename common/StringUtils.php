<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\common;

use framework\vendor\libphonenumber\NumberParseException;
use framework\vendor\libphonenumber\PhoneNumber;
use framework\vendor\libphonenumber\PhoneNumberFormat;
use framework\vendor\libphonenumber\PhoneNumberUtil;
use RuntimeException;
use Throwable;

class StringUtils
{
	public static function startsWith(string $str, string $startStr): bool
	{
		return (mb_strpos($str, $startStr) === 0);
	}

	public static function endsWith(string $str, string $endStr): bool
	{
		$endStrLen = mb_strlen($endStr);

		if (mb_strrpos($str, $endStr) + $endStrLen === mb_strlen($str)) {
			return true;
		}

		return false;
	}

	public static function beforeLast(string $str, string $before): string
	{
		$posUntil = mb_strrpos($str, $before);

		if ($posUntil === false) {
			return $str;
		}

		return mb_substr($str, 0, $posUntil);
	}

	public static function afterFirst(string $str, string $after): string
	{
		$posFrom = mb_strpos($str, $after);

		if ($posFrom === false) {
			return '';
		}

		$afterStr = mb_substr($str, $posFrom + mb_strlen($after));

		return ($afterStr !== false) ? $afterStr : '';
	}

	public static function beforeFirst(string $str, string $before): string
	{
		$posUntil = mb_strpos($str, $before);

		if ($posUntil === false) {
			return $str;
		}

		return mb_substr($str, 0, $posUntil);
	}

	public static function emptyToNull(string $string): ?string
	{
		return empty($string) ? null : $string;
	}

	public static function utf8_to_punycode(string $string)
	{
		return idn_to_ascii($string, 0, INTL_IDNA_VARIANT_UTS46);
	}

	public static function punycode_to_utf8(string $string)
	{
		return idn_to_utf8($string, 0, INTL_IDNA_VARIANT_UTS46);
	}

	/**
	 * Tries to sanitize/"nice format" a string, which is supposed to be a phone number, into an international format
	 * If the given string does not represent a valid phone number, it will return the trimmed, but
	 * otherwise unchanged string
	 *
	 * @param string $phone              : The phone number
	 * @param string $defaultCountryCode : The default country code if no international number is given
	 * @param bool   $internalFormat     : Format the phone number to be used internally, for example in epp
	 *
	 * @return string : Well formatted phone number in international format, if string was a valid phone number
	 */
	public static function phoneNumber(string $phone = '', string $defaultCountryCode = '', bool $internalFormat = false)
	{
		$phone = trim($phone);
		if ($phone === '') {
			return '';
		}

		if ($defaultCountryCode === '') {
			// If no international number is given, assume a swiss number or swiss as the default region, from where is being dialed
			$defaultCountryCode = 'CH';
		}

		$phoneNumber = self::parsePhoneNumber($phone, $defaultCountryCode);

		if (is_null($phoneNumber)) {
			// Just return original value, if phone number parsing fails
			return $phone;
		}

		if ($internalFormat) {
			return PhoneNumberUtil::PLUS_SIGN . $phoneNumber->getCountryCode() . '.' . $phoneNumber->getNationalNumber();
		} else {
			$phoneNumberUtil = PhoneNumberUtil::getInstance();

			return $phoneNumberUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);
		}
	}

	/**
	 * @param string $phone
	 * @param string $defaultCountryCode
	 *
	 * @return PhoneNumber|null : Parsed phone number or null if empty or no possible number
	 */
	public static function parsePhoneNumber(string $phone, string $defaultCountryCode): ?PhoneNumber
	{
		if (trim($phone) === '') {
			return null;
		}

		$countryCode = null;
		if (mb_strlen($phone) > 1 && mb_substr($phone, 0, 1) !== '+' && mb_substr($phone, 0, 2) !== '00') {
			// If no international number is given, assume country code from phone number field as the default region
			$countryCode = $defaultCountryCode;
		}
		$phoneNumberUtil = PhoneNumberUtil::getInstance();
		try {
			$phoneNumber = $phoneNumberUtil->parse($phone, $countryCode);
			/*
			 * NOTE: The following is a required fix when we use isPossibleNumber() instead of isValidNumber
			 * We need to use isPossibleNumber() instead of isValidNumber() so +41.123456789 is also accepted
			*/
			if ($phoneNumber->getCountryCode() !== 39) {
				$phoneNumber->setItalianLeadingZero(false);
			}

			return $phoneNumberUtil->isPossibleNumber($phoneNumber) ? $phoneNumber : null;
		} catch (NumberParseException $e) {
			return null;
		}
	}

	/**
	 * Generates a random character string of given length, where characters, which could be easily mixed up, were avoided.
	 * Set $cryptoSecurity to true to use a cryptographically secure pseudorandom number generator (random_int):
	 * - https://stackoverflow.com/a/31107425/31107425
	 * - http://stackoverflow.com/a/31284266/2224584
	 *
	 * @param int  $length         : length of desired string
	 * @param bool $noSpecialChars : if destination system does not accept special chars, set this to TRUE
	 * @param bool $cryptoSecurity : Use a slower/more complex cryptographically secure pseudorandom number generator
	 *
	 * @return string
	 */
	public static function randomString(int $length, bool $noSpecialChars, bool $cryptoSecurity = false): string
	{
		$length = ($length < 1) ? 1 : $length;
		$availableChars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPRSTUVWXYZ23456789';
		if (!$noSpecialChars) {
			$availableChars .= '!@#$%&*?';
		}
		$maxCharPos = mb_strlen($availableChars, '8bit') - 1;
		$string = '';
		for ($i = 0; $i < $length; $i++) {
			$randomCharacterPosition = $cryptoSecurity ? self::generateSecureRandomNumber($maxCharPos) : mt_rand(0, $maxCharPos);
			$string .= $availableChars[$randomCharacterPosition];
		}

		return $string;
	}

	private static function generateSecureRandomNumber(int $max, $exceptionCounter = 0): int
	{
		while ($exceptionCounter < 3) {
			try {
				return random_int(0, $max);
			} catch (Throwable $t) {
				// It was not possible to gather sufficient entropy
				$exceptionCounter++;
				// Give system some (raised) time to gain entropy again
				sleep($exceptionCounter * 4);
			}
		}

		throw new RuntimeException('Missing System entropy at generation of random string');
	}

	public static function sanitizeDomain(string $domain): ?string
	{
		$domain = mb_strtolower(trim($domain));
		if ($domain === '') {
			return '';
		}
		$rplArr = ['/\xE2\x80\x8B/', '@^[a-z]+://@i', '@^www\.@i', '/&#8203;/', '/\?/', '/ /', '/\/$/'];

		return preg_replace($rplArr, '', $domain);
	}
}
/* EOF */