<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\auth;

use LogicException;

abstract class AuthUser
{
	private static ?AuthUser $instance = null;

	public function __construct(
		public readonly int                    $ID,
		public readonly bool                   $isActive,
		private int                            $wrongPasswordAttempts,
		private readonly AccessRightCollection $accessRightCollection,
		private Password                       $password
	) {
		if (!is_null(value: AuthUser::$instance)) {
			throw new LogicException(message: 'There can only be one AuthUser instance.');
		}
		AuthUser::$instance = $this;
	}

	public function hasOneOfRights(AccessRightCollection $accessRightCollection): bool
	{
		if (!$this->isActive) {
			return false;
		}

		return $this->accessRightCollection->hasOneOfAccessRights(accessRightCollection: $accessRightCollection);
	}

	protected function changePassword(string $newUnencryptedPassword): void
	{
		$this->password = Password::generateNew(rawPassword: $newUnencryptedPassword);
	}

	public function getWrongPasswordAttempts(): int
	{
		return $this->wrongPasswordAttempts;
	}

	public function increaseWrongPasswordAttempts(): void
	{
		$this->dbIncreaseWrongPasswordAttempts();
		$this->wrongPasswordAttempts++;
	}

	public function confirmSuccessfulLogin(): int
	{
		$this->wrongPasswordAttempts = 0;

		return $this->dbConfirmSuccessfulLogin();
	}

	abstract protected function dbIncreaseWrongPasswordAttempts(): void;

	abstract protected function dbConfirmSuccessfulLogin(): int;

	public function getPassword(): Password
	{
		return $this->password;
	}

	protected static function resetInstance(): void
	{
		AuthUser::$instance = null;
	}
}