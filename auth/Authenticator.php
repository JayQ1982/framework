<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\auth;

use framework\core\HttpRequest;
use framework\session\AbstractSessionHandler;
use LogicException;

abstract class Authenticator
{
	private static ?Authenticator $instance = null;
	private AuthResult $authResult = AuthResult::UNDEFINED;

	protected function __construct(private readonly int $maxAllowedWrongPasswordAttempts)
	{
		if (!is_null(value: Authenticator::$instance)) {
			throw new LogicException(message: 'There can only be one Authenticator instance.');
		}
		Authenticator::$instance = $this;
	}

	protected function doLogin(string $userName, ?string $passwordToCheck): bool
	{
		if ($this->authResult !== AuthResult::UNDEFINED) {
			throw new LogicException(message: 'It is not allowed to execute this method multiple times.');
		}
		if (AuthSession::isLoggedIn()) {
			throw new LogicException(message: 'It is not allowed to log in, if user is already logged in.');
		}
		$sessionID = AbstractSessionHandler::getSessionHandler()->getID();
		$ipAddress = HttpRequest::getRemoteAddress();
		$authUser = $this->createAuthUserByUserName(userName: $userName);
		if (is_null(value: $authUser)) {
			$this->authResult = AuthResult::ERROR_UNKNOWN_USER_NAME;
			$this->logAuthResult(
				userID: null,
				sessionID: $sessionID,
				ip: $ipAddress,
				userName: $userName,
				authResult: $this->authResult
			);

			return false;
		}
		$userID = $authUser->ID;
		if (!$this->checkLoginCredentials(authUser: $authUser)) {
			if ($this->authResult === AuthResult::UNDEFINED) {
				throw new LogicException(message: 'Undefined authResult');
			}
			$this->logAuthResult(
				userID: $userID,
				sessionID: $sessionID,
				ip: $ipAddress,
				userName: $userName,
				authResult: $this->authResult
			);

			return false;
		}
		if (!$authUser->isActive) {
			$this->authResult = AuthResult::ERROR_INACTIVE;
			$this->logAuthResult(
				userID: $userID,
				sessionID: $sessionID,
				ip: $ipAddress,
				userName: $userName,
				authResult: $this->authResult
			);

			return false;
		}
		if ($authUser->getWrongPasswordAttempts() >= $this->maxAllowedWrongPasswordAttempts) {
			$this->authResult = AuthResult::ERROR_OUT_TRIED;
			$this->logAuthResult(
				userID: $userID,
				sessionID: $sessionID,
				ip: $ipAddress,
				userName: $userName,
				authResult: $this->authResult
			);

			return false;
		}
		if (is_null(value: $passwordToCheck)) {
			$this->authResult = AuthResult::SUCCESSFUL_SSO_LOGIN;
		} else {
			if (!$authUser->hasOneOfRights(
				accessRightCollection: AccessRightCollection::createFromStringArray(input: [AccessRightCollection::ACCESS_DO_PASSWORD_LOGIN]))
			) {
				$this->authResult = AuthResult::ERROR_NO_PASSWORD_LOGIN_ACTIVE;
				$this->logAuthResult(
					userID: $userID,
					sessionID: $sessionID,
					ip: $ipAddress,
					userName: $userName,
					authResult: $this->authResult
				);

				return false;
			}
			if (!$authUser->isPasswordValid(inputPassword: $passwordToCheck)) {
				$authUser->increaseWrongPasswordAttempts();
				$this->authResult = AuthResult::ERROR_WRONG_PASSWORD;
				$this->logAuthResult(
					userID: $userID,
					sessionID: $sessionID,
					ip: $ipAddress,
					userName: $userName,
					authResult: $this->authResult
				);

				return false;
			}
			$this->authResult = AuthResult::SUCCESSFUL_PASSWORD_LOGIN;
		}
		$this->logAuthResult(
			userID: $userID,
			sessionID: $sessionID,
			ip: $ipAddress,
			userName: $userName,
			authResult: $this->authResult
		);
		$authUser->confirmSuccessfulLogin();
		AuthSession::logIn(authUser: $authUser);

		return true;
	}

	public function passwordLogin(string $userName, string $inputPassword): bool
	{
		return $this->doLogin(userName: $userName, passwordToCheck: $inputPassword);
	}

	protected function authWebTokenLogin(AuthWebToken $authWebToken): bool
	{
		return $this->doLogin(userName: $authWebToken->getUserName(), passwordToCheck: null);
	}

	abstract protected function createAuthUserByUserName(string $userName): ?AuthUser;

	abstract protected function logAuthResult(?int $userID, string $sessionID, string $ip, string $userName, AuthResult $authResult);

	abstract protected function checkLoginCredentials(AuthUser $authUser): bool;

	protected function setAuthResult(AuthResult $authResult): void
	{
		$this->authResult = $authResult;
	}

	public function getAuthResult(): AuthResult
	{
		return $this->authResult;
	}
}