<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\auth;

use framework\session\AbstractSessionHandler;

class AuthSession
{
	private const SESSION_KEY = 'auth_userSession';
	private const isLoggedInIndicator = 'isLoggedIn';
	private const authSessionIdIndicator = 'authSessionID';

	final public static function logIn(int $authSessionID): void
	{
		AuthSession::setIsLoggedIn(isLoggedIn: true);
		AuthSession::setAuthSessionID(authSessionID: $authSessionID);
	}

	final public static function logOut(): void
	{
		if (!AuthSession::isLoggedIn()) {
			return;
		}
		AuthSession::resetSession();
	}

	private static function saveToSession(string $key, bool|int $value): void
	{
		$_SESSION[AuthSession::SESSION_KEY][$key] = $value;
	}

	private static function getFromSession(string $key): null|bool|int
	{
		if (!array_key_exists(key: AuthSession::SESSION_KEY, array: $_SESSION)) {
			$_SESSION[AuthSession::SESSION_KEY] = [];
		}
		if (!array_key_exists(key: $key, array: $_SESSION[AuthSession::SESSION_KEY])) {
			$_SESSION[AuthSession::SESSION_KEY][$key] = null;
		}

		return $_SESSION[AuthSession::SESSION_KEY][$key];
	}

	private static function setIsLoggedIn(bool $isLoggedIn): void
	{
		AuthSession::saveToSession(key: AuthSession::isLoggedInIndicator, value: $isLoggedIn);
	}

	private static function setAuthSessionID(int $authSessionID): void
	{
		AuthSession::saveToSession(key: AuthSession::authSessionIdIndicator, value: $authSessionID);
	}

	final public static function isLoggedIn(): bool
	{
		$isLoggedIn = AuthSession::getFromSession(key: AuthSession::isLoggedInIndicator);
		if (is_null(value: $isLoggedIn)) {
			AuthSession::setIsLoggedIn(isLoggedIn: false);

			return false;
		}

		return $isLoggedIn;
	}

	final public static function getAuthSessionID(): int
	{
		return AuthSession::getFromSession(key: AuthSession::authSessionIdIndicator);
	}

	private static function resetSession(): void
	{
		AuthSession::setIsLoggedIn(isLoggedIn: false);
		AuthSession::setAuthSessionID(authSessionID: 0);
		AbstractSessionHandler::getSessionHandler()->regenerateID();
	}
}