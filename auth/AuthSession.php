<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\auth;

use stdClass;

class AuthSession
{
	private const MNF = 'mnf_usersession';
	private static string $isLoggedInIndicator = 'isLoggedIn';
	private static string $userDataIndicator = 'userData';
	private static string $rightsIndicator = 'rights';
	private static string $lastActivityIndicator = 'lastActivity';

	private static function saveToSession(string $key, mixed $value): void
	{
		$_SESSION[AuthSession::MNF][$key] = $value;
	}

	private static function getFromSession(string $key): null|bool|array|stdClass|int
	{
		if (!array_key_exists(AuthSession::MNF, $_SESSION)) {
			$_SESSION[AuthSession::MNF] = [];
		}

		if (!array_key_exists($key, $_SESSION[AuthSession::MNF])) {
			$_SESSION[AuthSession::MNF][$key] = null;
		}

		return $_SESSION[AuthSession::MNF][$key];
	}

	final public static function setIsLoggedIn(bool $isLoggedIn): void
	{
		AuthSession::saveToSession(AuthSession::$isLoggedInIndicator, $isLoggedIn);
	}

	final public static function isLoggedIn(): bool
	{
		$indicator = AuthSession::$isLoggedInIndicator;
		$isLoggedIn = AuthSession::getFromSession($indicator);
		if (is_null($isLoggedIn)) {
			$isLoggedIn = false;
			AuthSession::saveToSession($indicator, false);
		}

		return $isLoggedIn;
	}

	final public static function setUserData(stdClass $userData): void
	{
		AuthSession::saveToSession(AuthSession::$userDataIndicator, $userData);
	}

	final public static function getUserData(): stdClass
	{
		$indicator = AuthSession::$userDataIndicator;
		$userData = AuthSession::getFromSession($indicator);
		if (is_null($userData)) {
			$userData = new stdClass();
			AuthSession::saveToSession($indicator, $userData);
		}

		return $userData;
	}

	final public static function setRights(array $rights): void
	{
		AuthSession::saveToSession(AuthSession::$rightsIndicator, $rights);
	}

	final public static function getRights(): array
	{
		$indicator = AuthSession::$rightsIndicator;
		$rights = AuthSession::getFromSession($indicator);
		if (is_null($rights)) {
			$rights = [];
			AuthSession::saveToSession($indicator, $rights);
		}

		return $rights;
	}

	final public static function updateLastActivity(int $lastActivity): void
	{
		AuthSession::saveToSession(AuthSession::$lastActivityIndicator, $lastActivity);
	}

	final public static function getLastActivity(): ?int
	{
		return AuthSession::getFromSession(AuthSession::$lastActivityIndicator);
	}

	final public static function resetSession(): void
	{
		if (array_key_exists(AuthSession::MNF, $_SESSION)) {
			unset($_SESSION[AuthSession::MNF]);
		}
	}
}