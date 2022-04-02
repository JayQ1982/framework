<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use DateTime;
use framework\datacheck\Sanitizer;
use Throwable;

class FrameworkUtils
{
	public static function getErrorArrayAsHtml(?array $errors): ?string
	{
		if (is_null($errors) || count($errors) === 0) {
			return null;
		}

		$errorHtml = '<ul class="error">' . PHP_EOL;
		foreach ($errors as $error) {
			$errorHtml .= '<li>' . $error . '</li>' . PHP_EOL;
		}
		$errorHtml .= '</ul>';

		return $errorHtml;
	}

	public static function strToDate(string $value): ?DateTime
	{
		if ($value !== '') {
			try {
				return new DateTime($value);
			} catch (Throwable) {
				return null;
			}
		}

		return null;
	}

	public static function castTypeToString($value): ?string
	{
		if (is_null($value)) {
			return null;
		}

		if (is_object($value)) {
			if ($value instanceof DateTime) {
				return $value->format('Y-m-d H:i:s');
			}

			return (string)$value;
		}

		return $value;
	}

	/**
	 * Creates a kind of "hash" for a given array
	 *
	 * @param array $array
	 *
	 * @return string
	 */
	public static function arrayToCode(array $array): string
	{
		$string = '';
		foreach ($array as $key => $val) {
			$string .= $key . $val;
		}

		return base64_encode($string);
	}

	public static function validateEmail(string $email): bool
	{
		if ($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return false;
		}

		return true;
	}

	public static function objectToArray($d): array
	{
		if (is_object($d)) {
			$d = get_object_vars($d);
		}

		if (is_array($d)) {
			return array_map([__CLASS__, __FUNCTION__], $d);
		}

		return $d;
	}

	public static function cleanNumber(float|int $number): float
	{
		$number = (string)$number;

		$localeconv = localeconv();
		$mon_thousands_sep = ($localeconv['mon_thousands_sep'] == '') ? "'" : $localeconv['mon_thousands_sep'];
		$mon_decimal_point = ($localeconv['mon_decimal_point'] == '') ? '.' : $localeconv['mon_decimal_point'];

		$mon_thousands_sep = str_replace('.', '\.', $mon_thousands_sep);
		$mon_decimal_point = str_replace('.', '\.', $mon_decimal_point);

		if (preg_match('/^-?[0-9]+\.[0-9]+$/', $number)) {
			return floatval($number);
		}

		if (preg_match('/^-?[0-9]+' . $mon_thousands_sep . '[0-9]+' . $mon_decimal_point . '[0-9]+$/', $number)) {
			return floatval(str_replace([$mon_thousands_sep, $mon_decimal_point], [null, '.'], $number));
		}

		if (preg_match('/^-?[0-9]+' . $mon_decimal_point . '[0-9]+$/', $number)) {
			return floatval(str_replace([$mon_decimal_point], ['.'], $number));
		}

		if (preg_match('/^-?[0-9]+,[0-9]+$/', $number)) {
			return floatval(str_replace([','], ['.'], $number));
		}

		return floatval($number);
	}

	public static function getCurrency(): string
	{
		$intCurrSymbol = Sanitizer::trimmedString(localeconv()['int_curr_symbol']);

		return ($intCurrSymbol === '') ? 'CHF' : $intCurrSymbol;
	}

	/**
	 * Formats a number as an amount in the current locale standard
	 *
	 * @param float|int $amount The number to format
	 *
	 * @return string The formatted amount
	 */
	public static function formatAsAmount(float|int $amount): string
	{
		$localeconv = localeconv();
		$int_curr_symbol = ($localeconv['int_curr_symbol'] == '') ? "CHF" : $localeconv['int_curr_symbol'];
		$mon_decimal_point = ($localeconv['mon_decimal_point'] == '') ? '.' : $localeconv['mon_decimal_point'];
		$mon_thousands_sep = ($localeconv['mon_thousands_sep'] == '') ? "'" : $localeconv['mon_thousands_sep'];

		$amount = FrameworkUtils::cleanNumber($amount);

		return $int_curr_symbol . ' ' . number_format($amount, 2, $mon_decimal_point, $mon_thousands_sep);
	}

	/**
	 * Formats a number in the current locale standard
	 *
	 * @param float|int $number The number to format
	 *
	 * @return string The formatted number
	 */
	public static function formatAsNumber(float|int $number): string
	{
		$localeconv = localeconv();
		$mon_decimal_point = ($localeconv['mon_decimal_point'] == '') ? '.' : $localeconv['mon_decimal_point'];
		$mon_thousands_sep = ($localeconv['mon_thousands_sep'] == '') ? "'" : $localeconv['mon_thousands_sep'];

		$number = FrameworkUtils::cleanNumber($number);

		return number_format($number, 2, $mon_decimal_point, $mon_thousands_sep);
	}

	public static function displaySelectOptions(array $optionsArr, $curr, int $empty = 0): string
	{
		if (!is_array($curr)) {
			$curr = [$curr];
		}

		$options = '';
		if ($empty === 1) {
			$options .= "<option value=\"0\">&nbsp;</option>\n";
		}

		foreach ($optionsArr as $key => $val) {
			$options .= '<option value="' . $key . '"';
			if (in_array($key, $curr)) {
				$options .= ' selected="selected"';
			}
			$options .= '>' . $val . '</option>' . PHP_EOL;
		}

		return $options;
	}
}