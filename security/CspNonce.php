<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\security;

class CspNonce
{
	private static ?string $cspNonce = null;

	public static function get(): string
	{
		/* Hint: This method MIGHT (seldom) be called BEFORE
		 * $_SESSION has been initialized (for example: error-pages).
		 * Trying to use same nonce within a user-session, if possible.
		 */
		if (!isset($_SESSION)) {
			if (is_null(self::$cspNonce)) {
				self::$cspNonce = self::generate();
			}

			return self::$cspNonce;
		}
		if (!isset($_SESSION['security_cspNonce'])) {
			if (is_null(self::$cspNonce)) {
				self::$cspNonce = self::generate();
			}
			$_SESSION['security_cspNonce'] = self::$cspNonce;
		}

		return $_SESSION['security_cspNonce'];
	}

	private static function generate(): string
	{
		return base64_encode(openssl_random_pseudo_bytes(16));
	}
}

/* EOF */