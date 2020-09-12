<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\security;

class CsrfToken
{
	const CSRFTOKENSTORAGE = 'csrftoken';

	/**
	 * Returns a CSRF-Token (generate and stores it, if not already done)
	 *
	 * @param bool $forceNew : (optional) if set to true, the old Token will be replaced
	 *
	 * @return string
	 */
	public static function getToken($forceNew = false): string
	{
		if ($forceNew) {
			unset($_SESSION[self::CSRFTOKENSTORAGE]);
		}
		if (!isset($_SESSION[self::CSRFTOKENSTORAGE])) {
			$_SESSION[self::CSRFTOKENSTORAGE] = base64_encode(openssl_random_pseudo_bytes(32));
		}

		return $_SESSION[self::CSRFTOKENSTORAGE];
	}

	public static function renderAsGetParam(): string
	{
		return self::CSRFTOKENSTORAGE . '=' . urlencode(self::getToken());
	}

	/**
	 * Renders CSRF-Token as $_POST - form input field
	 *
	 * @return string : HTML
	 */
	public static function renderAsHiddenPostField(): string
	{
		return '<input type="hidden" name="' . self::CSRFTOKENSTORAGE . '" value="' . htmlentities(self::getToken()) . '">';
	}

	public static function getFieldName(): string
	{
		return self::CSRFTOKENSTORAGE;
	}
}
/* EOF */
