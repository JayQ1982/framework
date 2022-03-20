<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\auth;

use framework\core\Core;
use framework\core\HttpRequest;
use framework\core\RequestHandler;
use framework\db\FrameworkDB;
use framework\exception\UnauthorizedException;
use LogicException;
use stdClass;

class Authenticator
{
	public const ERROR_UNKNOWN = 0;
	public const ERROR_NO_ERROR = 1;
	public const ERROR_NO_LOGIN = 2;
	public const ERROR_NO_PASSWORD = 3;
	public const ERROR_WRONG_LOGIN = 4;
	public const ERROR_INACTIVE = 5;
	public const ERROR_OUTTRIED = 7;
	public const ERROR_WRONG_PASSWORD = 8;
	public const ERROR_NOT_CONFIRMED = 9;

	private static ?Authenticator $instance = null;

	private Core $core;
	private AuthSettings $authSettings;
	private int $lastErrorCode = Authenticator::ERROR_UNKNOWN;
	private ?FrameworkDB $authDb = null;
	private ?stdClass $loginUserDataCache = null;

	public static function getInstance(Core $core, AuthSettings $authSettings): Authenticator
	{
		if (is_null(Authenticator::$instance)) {
			Authenticator::$instance = new Authenticator($core, $authSettings);
		}

		return Authenticator::$instance;
	}

	protected static function setInstance(Authenticator $authenticator)
	{
		Authenticator::$instance = $authenticator;
	}

	protected function __construct(Core $core, AuthSettings $authSettings)
	{
		$this->core = $core;
		$this->authSettings = $authSettings;

		$now = time();
		$lastActivity = AuthSession::getLastActivity();
		if (!is_null($lastActivity) && $this->isLoggedIn() && $now > $lastActivity + $authSettings->getMaxIdleSeconds()) {
			$this->logout();
		}
		AuthSession::updateLastActivity($now);
	}

	protected function checkLoginCredentials(string $login, string $password): bool
	{
		$userDataFromDb = $this->getUserDataFromDb($login, false);
		if ($this->getLastErrorCode() === Authenticator::ERROR_WRONG_LOGIN) {
			return false;
		}

		if (property_exists($userDataFromDb, 'confirmed') && is_null($userDataFromDb->confirmed)) {
			$this->setCredentialCheckError(Authenticator::ERROR_NOT_CONFIRMED);

			return false;
		}

		if (isset($userDataFromDb->active) && (int)$userDataFromDb->active !== 1) {
			$this->setCredentialCheckError(Authenticator::ERROR_INACTIVE);

			return false;
		}

		$authSettings = $this->authSettings;
		if (isset($userDataFromDb->wronglogin) && $userDataFromDb->wronglogin >= $authSettings->getMaxAllowedWrongPasswordAttempts()) {
			$this->setCredentialCheckError(Authenticator::ERROR_OUTTRIED);

			return false;
		}

		$salt = $userDataFromDb->salt ?? 'noSalt';
		$inputPwHash = $this->encryptPassword($salt, $password);

		if (!isset($userDataFromDb->password) || $userDataFromDb->password !== $inputPwHash) {
			$wrongPasswordQuery = $authSettings->getWrongPasswordQuery();
			if (!is_null($wrongPasswordQuery)) {
				$this->getAuthDb()->execute($wrongPasswordQuery, [$login]);
			}
			$this->setCredentialCheckError(Authenticator::ERROR_WRONG_PASSWORD);

			return false;
		}

		$this->setLastErrorCode(Authenticator::ERROR_NO_ERROR);

		return true;
	}

	public function getSettings(): AuthSettings
	{
		return $this->authSettings;
	}

	protected function getUserDataFromDb(string $login, bool $forceReload): stdClass
	{
		if (!$forceReload && !is_null($this->loginUserDataCache)) {
			return $this->loginUserDataCache;
		}

		$db = $this->getAuthDb();
		$res = $db->select($this->authSettings->getCheckLoginQuery(), [$login]);
		if (count($res) !== 1) {
			$this->setCredentialCheckError(Authenticator::ERROR_WRONG_LOGIN);
			$this->loginUserDataCache = new stdClass();
		} else {
			$this->loginUserDataCache = $res[0];
		}

		return $this->loginUserDataCache;
	}

	private function resetLoginUserData(): void
	{
		$this->loginUserDataCache = null;
	}

	protected function doFullLogin(string $login): void
	{
		if ($this->getLastErrorCode() !== Authenticator::ERROR_NO_ERROR) {
			throw new LogicException('It is not allowed to do a full login if Authenticator has errors');
		}
		if ($this->isLoggedIn()) {
			throw new LogicException('It is not allowed to do a full login if user is already logged in');
		}
		$this->regenerateSessionID();
		AuthSession::setIsLoggedIn(true);
		AuthSession::setUserData($this->getUserDataFromDb($login, false));
		AuthSession::setRights($this->getRightsForLogin($login));

		$this->getAuthDb()->execute($this->authSettings->getConfirmLoginQuery(), [$login]);
	}

	private function getRightsForLogin(string $login): array
	{
		$loadRightsQuery = $this->authSettings->getLoadRightsQuery();
		if (is_null($loadRightsQuery)) {
			return [];
		}
		$accessRights = [];
		$res = $this->getAuthDb()->select($loadRightsQuery, [$login]);
		foreach ($res as $row) {
			$accessRights[] = $row->accessright;
		}

		return $accessRights;
	}

	public function doLogin(string $login, string $password): bool
	{
		if (!$this->checkLoginCredentials($login, $password)) {
			return false;
		}

		$this->doFullLogin($login);

		return true;
	}

	public function getLastErrorCode(): int
	{
		return $this->lastErrorCode;
	}

	protected function setCredentialCheckError(int $lastErrorCode): void
	{
		$this->resetLoginUserData();
		$this->setLastErrorCode($lastErrorCode);
	}

	protected function setLastErrorCode(int $lastErrorCode): void
	{
		$this->lastErrorCode = $lastErrorCode;
	}

	public function logout(): void
	{
		if (!$this->isLoggedIn()) {
			return;
		}

		AuthSession::resetSession();
		$this->regenerateSessionID();
	}

	protected function regenerateSessionID(): void
	{
		$this->core->getSessionHandler()->regenerateID();
	}

	public function checkAccess(bool $accessOnlyForLoggedInUsers, array $requiredAccessRights, bool $autoRedirect): bool
	{
		$hasAccess = $this->doAccessCheck($accessOnlyForLoggedInUsers, $requiredAccessRights);
		if (!$hasAccess && $autoRedirect) {
			$requestHandler = RequestHandler::getInstance();
			if ($requestHandler->getRoute() === $requestHandler->getDefaultRoutesByLanguageCode()[$requestHandler->getLanguage()]) {
				$this->redirectToLoginPage();
			}

			throw new UnauthorizedException();
		}

		return $hasAccess;
	}

	public function redirectToLoginPage(): void
	{
		$pageAfterLogin = base64_encode(HttpRequest::getURI());
		$this->core->redirect($this->authSettings->getLoginPage() . '?pageAfterLogin=' . $pageAfterLogin);
	}

	private function doAccessCheck(bool $accessOnlyForLoggedInUsers, array $requiredAccessRights): bool
	{
		if (!$this->isLoggedIn()) {
			return !$accessOnlyForLoggedInUsers;
		}

		// User is logged in
		if (!$accessOnlyForLoggedInUsers) {
			return false;
		}

		// Content is only for logged in users
		if (count($requiredAccessRights) === 0) {
			return true;
		}

		$userAccessRights = AuthSession::getRights();
		foreach ($requiredAccessRights as $requiredRight) {
			if (in_array($requiredRight, $userAccessRights)) {
				return true;
			}
		}

		return false;
	}

	protected function getAuthDb(): FrameworkDB
	{
		if (is_null($this->authDb)) {
			$this->authDb = FrameworkDB::getInstance($this->authSettings->getAuthDbSettingsModel());
		}

		return $this->authDb;
	}

	public function isLoggedIn(): bool
	{
		return AuthSession::isLoggedIn();
	}

	public function encryptPassword(string $salt, string $password): string
	{
		return hash($this->authSettings->getHashAlgorithm(), $salt . $password);
	}
}