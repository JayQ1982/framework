<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
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
	public static function getToken(bool $forceNew = false): string
	{
		if ($forceNew) {
			unset($_SESSION[CsrfToken::CSRFTOKENSTORAGE]);
		}
		if (!isset($_SESSION[CsrfToken::CSRFTOKENSTORAGE])) {
			$_SESSION[CsrfToken::CSRFTOKENSTORAGE] = base64_encode(openssl_random_pseudo_bytes(32));
		}

		return $_SESSION[CsrfToken::CSRFTOKENSTORAGE];
	}

	public static function renderAsGetParam(): string
	{
		return CsrfToken::CSRFTOKENSTORAGE . '=' . urlencode(CsrfToken::getToken());
	}

	/**
	 * Renders CSRF-Token as $_POST - form input field
	 *
	 * @return string : HTML
	 */
	public static function renderAsHiddenPostField(): string
	{
		// The token is a base64_encoded string which doesn't contain characters that need to be html encoded for rendering
		return '<input type="hidden" name="' . CsrfToken::CSRFTOKENSTORAGE . '" value="' . CsrfToken::getToken() . '">';
	}

	public static function getFieldName(): string
	{
		return CsrfToken::CSRFTOKENSTORAGE;
	}

	public static function validateToken(string $token): bool
	{
		return ($token === CsrfToken::getToken());
	}
}