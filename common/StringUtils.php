<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
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

		return mb_substr($str, $posFrom + mb_strlen($after));
	}

	public static function beforeFirst(string $str, string $before): string
	{
		$posUntil = mb_strpos($str, $before);
		if ($posUntil === false) {
			return $str;
		}

		return mb_substr($str, 0, $posUntil);
	}

	public static function between(string $str, string $start, string $end): ?string
	{
		$posStart = mb_strpos($str, $start) + mb_strlen($start);
		$posEnd = mb_strrpos($str, $end, $posStart);
		if ($posEnd === false) {
			return null;
		}

		return mb_substr($str, $posStart, $posEnd - $posStart);
	}

	public static function afterLast(string $str, string $after): ?string
	{
		$posFrom = mb_strrpos($str, $after);

		if ($posFrom === false) {
			return null;
		}

		return mb_substr($str, $posFrom + mb_strlen($after));
	}

	public static function insertBeforeLast(string $str, string $beforeLast, string $newStr): string
	{
		return StringUtils::beforeLast($str, $beforeLast) . $newStr . $beforeLast . StringUtils::afterLast($str, $beforeLast);
	}

	public static function startsWith(string $str, string $startStr): bool
	{
		return (mb_strpos($str, $startStr) === 0);
	}

	public static function endsWith(string $str, string $endStr): bool
	{
		$endStrLen = mb_strlen($endStr);

		return (mb_strrpos($str, $endStr) + $endStrLen === mb_strlen($str));
	}

	public static function breakUp(string $sentence, int $atIndex): string
	{
		if (mb_strlen($sentence) > $atIndex) {
			return StringUtils::beforeLast(mb_substr($sentence, 0, 50), " ");
		}

		return $sentence;
	}

	public static function tokenize(string $stringToSplit, string $tokenToSplitString): array
	{
		$tokenArr = [];
		$tokStr = strtok($stringToSplit, $tokenToSplitString);

		while ($tokStr !== false) {
			$tokenArr[] = $tokStr;

			$tokStr = strtok($tokenToSplitString);
		}

		return $tokenArr;
	}

	public static function explode(string|array $tokens, string $str): array
	{
		$strToExplode = $str;
		$explodeStr = $tokens;

		if (is_array($tokens) === true) {
			$explodeStr = chr(31);
			$strToExplode = str_replace($tokens, $explodeStr, $str);
		}

		return explode($explodeStr, $strToExplode);
	}

	/**
	 * @param string $str       The string to urlify
	 * @param int    $maxLength The max length of the urlified string. 0 is no length limit.
	 *
	 * @return string The urlified string
	 */
	public static function urlify(string $str, int $maxLength = 0): string
	{
		$charMap = [
			' '  => '-',
			'.'  => '',
			':'  => '',
			','  => '',
			'?'  => '',
			'!'  => '',
			'´'  => '',
			'"'  => '',
			'('  => '',
			')'  => '',
			'['  => '',
			']'  => '',
			'{'  => '',
			'}'  => '',
			'\'' => '',

			// German
			'ä'  => 'ae',
			'ö'  => 'oe',
			'ü'  => 'ue',

			// Français
			'é'  => 'e',
			'è'  => 'e',
			'ê'  => 'e',
			'à'  => 'a',
			'â'  => 'a',
			'ç'  => 'c',
			'ï'  => '',
			'î'  => '',

			// Español
			'ñ'  => 'n',
			'ó'  => 'o',
			'ú'  => 'u',
			'¿'  => '',
			'¡'  => '',
		];

		$urlifiedStr = str_replace(array_keys($charMap), $charMap, strtolower(trim($str)));

		// Replace multiple dashes
		$urlifiedStr = preg_replace('/[-]{2,}/', '-', $urlifiedStr);

		if ($maxLength === 0) {
			return $urlifiedStr;
		}

		return substr($urlifiedStr, 0, $maxLength);
	}

	public static function emptyToNull(string $string): ?string
	{
		return ($string === '') ? null : $string;
	}

	public static function utf8_to_punycode(string $string): false|string
	{
		return idn_to_ascii($string, 0, INTL_IDNA_VARIANT_UTS46);
	}

	public static function punycode_to_utf8(string $string): false|string
	{
		return idn_to_utf8($string, 0, INTL_IDNA_VARIANT_UTS46);
	}

	public static function formatBytes($bytes, int $precision = 2): float
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);

		// $bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . ' ' . $units[$pow];
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
	public static function phoneNumber(string $phone = '', string $defaultCountryCode = '', bool $internalFormat = false): string
	{
		$phone = trim($phone);
		if ($phone === '') {
			return '';
		}

		if ($defaultCountryCode === '') {
			// If no international number is given, assume a swiss number or swiss as the default region, from where is being dialed
			$defaultCountryCode = 'CH';
		}

		$phoneNumber = StringUtils::parsePhoneNumber($phone, $defaultCountryCode);

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

		if (str_starts_with($phone, '00')) {
			$phone = '+' . substr($phone, 2);
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
		} catch (NumberParseException) {
			return null;
		}
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
			$randomCharacterPosition = $cryptoSecurity ? StringUtils::generateSecureRandomNumber($maxCharPos) : mt_rand(0, $maxCharPos);
			$string .= $availableChars[$randomCharacterPosition];
		}

		return $string;
	}

	private static function generateSecureRandomNumber(int $max): int
	{
		$exceptionCounter = 0;
		while ($exceptionCounter < 3) {
			try {
				return random_int(0, $max);
			} catch (Throwable) {
				// It was not possible to gather sufficient entropy
				$exceptionCounter++;
				// Give system some (raised) time to gain entropy again
				sleep($exceptionCounter * 4);
			}
		}

		throw new RuntimeException('Missing System entropy at generation of random string');
	}

	public static function generateSalt(int $length = 16): string
	{
		$chars = '`´°+*ç%&/()=?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890üöä!£{}éèà[]¢|¬§°#@¦';
		$charsLength = mb_strlen($chars);
		srand((double)microtime() * 1000000);
		$salt = '';
		for ($i = 0; $i < $length; $i++) {
			$salt .= mb_substr($chars, (rand() % $charsLength), 1);
		}

		return $salt;
	}
}