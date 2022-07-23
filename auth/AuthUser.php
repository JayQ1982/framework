<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\auth;

use framework\common\StringUtils;
use LogicException;

abstract class AuthUser
{
	private static ?AuthUser $instance = null;

	public function __construct(
		public readonly int                    $ID,
		public readonly bool                   $isActive,
		private int                            $wrongPasswordAttempts,
		private readonly AccessRightCollection $accessRightCollection,
		private string                         $salt,
		private string                         $password,
		private readonly string                $passwordHashAlgorithm = 'sha256'
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

	public function isPasswordValid(string $inputPassword): bool
	{
		return ($this->encryptPasswordString(inputPassword: $inputPassword) === $this->password);
	}

	protected function changePassword(string $newUnencryptedPassword): void
	{
		$this->salt = StringUtils::generateSalt();
		$this->password = $this->encryptPasswordString(inputPassword: $newUnencryptedPassword);
	}

	private function encryptPasswordString(string $inputPassword): string
	{
		return hash(algo: $this->passwordHashAlgorithm, data: $this->salt . $inputPassword);
	}

	public function getWrongPasswordAttempts(): int
	{
		return $this->wrongPasswordAttempts;
	}

	public function increaseWrongPasswordAttempts(): void
	{
		$this->dbIncreaseWrongPasswordAttempts(userID: $this->ID);
		$this->wrongPasswordAttempts++;
	}

	public function confirmSuccessfulLogin(): void
	{
		$this->dbConfirmSuccessfulLogin(userID: $this->ID);
		$this->wrongPasswordAttempts = 0;
	}

	abstract protected function dbIncreaseWrongPasswordAttempts(int $userID): void;

	abstract protected function dbConfirmSuccessfulLogin(int $userID): void;

	protected function getSalt(): string
	{
		return $this->salt;
	}

	protected function getPassword(): string
	{
		return $this->password;
	}
}