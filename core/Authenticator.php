<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use Exception;
use stdClass;

class Authenticator
{
	private const HASH_ALG = 'sha256';
	private const SALT_LENGTH = 16;

	private Core $core;
	private bool $isLoggedIn;
	private stdClass $userData;
	private array $accessRights;

	public function __construct(Core $core)
	{
		$this->core = $core;
		$this->userData = new stdClass();

		$this->isLoggedIn = array_key_exists('isLoggedIn', $_SESSION) ? $_SESSION['isLoggedIn'] : false;
		$this->userData = array_key_exists('userData', $_SESSION) ? $_SESSION['userData'] : new stdClass();
		$this->accessRights = array_key_exists('accessRights', $_SESSION) ? $_SESSION['accessRights'] : [];
	}

	public function checkAccess(bool $mustBeLoggedIn, array $requiredAccessRights, bool $autoRedirect = false): bool
	{
		$hasAccess = $this->doAccessCheck($mustBeLoggedIn, $requiredAccessRights);
		if (!$hasAccess && $autoRedirect) {
			$this->redirect();
		}

		return $hasAccess;
	}

	private function doAccessCheck(bool $mustBeLoggedIn, array $requiredAccessRights): bool
	{
		if (!$this->isLoggedIn()) {
			return $mustBeLoggedIn ? false : true;
		}

		// User is logged in
		if (!$mustBeLoggedIn) {
			// Content is only for not logged in users
			return false;
		}

		// Content is only for logged in users

		if (count($requiredAccessRights) === 0) {
			// There are no accessRight restrictions
			return true;
		}

		// AccessRight restrictions
		$userAccessRights = $this->getAccessRights();
		foreach ($requiredAccessRights as $requiredRight) {
			if (!is_string($requiredRight)) {
				throw new Exception('Access rights must be defined as string!');
			}
			if (in_array($requiredRight, $userAccessRights)) {
				return true;
			}
		}

		return false;
	}

	public function isLoggedIn(): bool
	{
		return $this->isLoggedIn;
	}

	public function getUserData(): stdClass
	{
		return $this->userData;
	}

	public function getAccessRights(): array
	{
		return $this->accessRights;
	}

	private function redirect(string $redirectTarget = ''): void
	{
		$core = $this->core;
		$goto = base64_encode($core->getHttpRequest()->getURI());

		if ($redirectTarget === '') {
			$core->redirect($core->getEnvironmentHandler()->getDefaultAccessDeniedPage() . '?pageAfterLogin=' . $goto);
		} else {
			$core->redirect($redirectTarget . '?pageAfterLogin=' . $goto);
		}
	}

	public function logout(): void
	{
		if ($this->isLoggedIn()) {
			$this->core->getSessionHandler()->regenerateID();

			$_SESSION['isLoggedIn'] = $this->isLoggedIn = false;
			$_SESSION['userData'] = $this->userData = new stdClass();
			$_SESSION['accessRights'] = $this->accessRights = [];
		}
	}

	public function encryptPassword(string $salt, string $password): string
	{
		return hash(self::HASH_ALG, $salt . $password);
	}

	public function generateSalt(): string
	{
		$chars = utf8_decode('`´°+*ç%&/()=?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890üöä!£{}éèà[]¢|¬§°#@¦');
		$charsLngth = strlen($chars);

		srand((double)microtime() * 1000000);
		$salt = '';

		for ($i = 0; $i < self::SALT_LENGTH; $i++) {
			$num = rand() % $charsLngth;

			$tmp = utf8_encode(substr($chars, $num, 1));
			$salt .= $tmp;
		}

		return $salt;
	}
}
/* EOF */