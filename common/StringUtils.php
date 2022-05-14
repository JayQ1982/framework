<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

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

		$urlifiedStr = str_replace(array_keys($charMap), $charMap, mb_strtolower(trim(string: $str)));

		// Replace multiple dashes
		$urlifiedStr = preg_replace(
			pattern: '/-{2,}/',
			replacement: '-',
			subject: $urlifiedStr
		);

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

	public static function utf8_to_punycode_email(string $email): string
	{
		$fragments = explode('@', $email);
		$lastFragment = array_pop($fragments);

		return implode('@', $fragments) . '@' . StringUtils::utf8_to_punycode($lastFragment);
	}

	public static function punycode_to_utf8(string $string): false|string
	{
		return idn_to_utf8($string, 0, INTL_IDNA_VARIANT_UTS46);
	}

	public static function punycode_to_utf8_email(string $email): string
	{
		$fragments = explode('@', $email);
		$lastFragment = array_pop($fragments);

		return implode('@', $fragments) . '@' . StringUtils::punycode_to_utf8($lastFragment);
	}

	public static function formatBytes(int|float $bytes, int $precision = 2): string
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];

		$bytes = max($bytes, 0);
		$pow = floor(num: ($bytes ? log(num: $bytes) : 0) / log(num: 1024));
		$pow = min($pow, count(value: $units) - 1);
		$bytes /= pow(num: 1024, exponent: $pow);

		return round(num: $bytes, precision: $precision) . ' ' . $units[$pow];
	}

	/**
	 * Generates a random character string of given length, where characters, which could be easily mixed up, were avoided.
	 * Set $cryptoSecurity to true to use a cryptographically secure pseudorandom number generator (random_int):
	 * - https://stackoverflow.com/a/31107425/31107425
	 * - http://stackoverflow.com/a/31284266/2224584
	 *
	 * @param int  $requiredStringLength Required length of the random string
	 * @param bool $noSpecialChars       Set to true to only use numbers and letters
	 *
	 * @return string
	 */
	public static function randomString(int $requiredStringLength, bool $noSpecialChars): string
	{
		$requiredStringLength = ($requiredStringLength < 1) ? 1 : $requiredStringLength;

		$characterSets = [
			'abcdefghjkmnpqrstuvwxyz',
			'ABCDEFGHJKMNPQRSTUVWXYZ',
			'23456789',
		];
		if (!$noSpecialChars) {
			$characterSets[] = '!@#$%&*?';
		}

		$unShuffledRandomString = '';
		foreach ($characterSets as $characterSet) {
			$unShuffledRandomString .= $characterSet[mt_rand(
				min: 0,
				max: mb_strlen(string: $characterSet, encoding: '8bit') - 1
			)];
		}

		$allCharacters = implode('', $characterSets);
		$currentRandomStringLength = mb_strlen(string: $unShuffledRandomString);
		while ($currentRandomStringLength < $requiredStringLength) {
			$unShuffledRandomString .= $allCharacters[mt_rand(
				min: 0,
				max: mb_strlen(string: $allCharacters, encoding: '8bit') - 1
			)];
			$currentRandomStringLength++;
		}

		return str_shuffle(string: $unShuffledRandomString);
	}

	public static function generateSalt(int $length = 16): string
	{
		$chars = '`´°+*ç%&/()=?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890üöä!£{}éèà[]¢|¬§°#@¦';
		$charsLength = mb_strlen($chars);
		$salt = '';
		for ($i = 0; $i < $length; $i++) {
			$salt .= mb_substr($chars, (rand() % $charsLength), 1);
		}

		return $salt;
	}
}