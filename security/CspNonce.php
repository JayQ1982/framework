<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\security;

class CspNonce
{
	private const SESSION_INDICATOR = 'security_cspNonce';
	private static ?string $cspNonce = null;

	public static function get(): string
	{
		if (!isset($_SESSION)) {
			// This method might be called before $_SESSION has been initialized (e.g. error-pages)
			if (is_null(CspNonce::$cspNonce)) {
				CspNonce::$cspNonce = CspNonce::generate();
			}

			return CspNonce::$cspNonce;
		}
		if (!isset($_SESSION[CspNonce::SESSION_INDICATOR])) {
			if (is_null(CspNonce::$cspNonce)) {
				CspNonce::$cspNonce = CspNonce::generate();
			}
			$_SESSION[CspNonce::SESSION_INDICATOR] = CspNonce::$cspNonce;
		}

		return $_SESSION[CspNonce::SESSION_INDICATOR];
	}

	private static function generate(): string
	{
		return base64_encode(openssl_random_pseudo_bytes(16));
	}
}