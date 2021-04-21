<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\auth;

use framework\db\DbSettingsModel;

class AuthSettings
{
	private ?DbSettingsModel $authDbSettingsModel;
	private string $checkLoginQuery;
	private ?string $wrongPasswordQuery;
	private ?string $loadRightsQuery;
	private ?string $confirmLoginQuery;
	private int $saltLength = 16;
	private int $maxAllowedWrongPasswordAttempts = 5;
	private int $maxIdleSeconds = 7200;
	private string $hashAlgorithm = 'sha256';
	private string $loginPage = 'login.html';

	public function __construct(?DbSettingsModel $authDbSettingsModel, string $checkLoginQuery, ?string $wrongPasswordQuery, ?string $loadRightsQuery, ?string $confirmLoginQuery)
	{
		$this->authDbSettingsModel = $authDbSettingsModel;
		$this->checkLoginQuery = $checkLoginQuery;
		$this->wrongPasswordQuery = $wrongPasswordQuery;
		$this->loadRightsQuery = $loadRightsQuery;
		$this->confirmLoginQuery = $confirmLoginQuery;
	}

	public function getAuthDbSettingsModel(): ?DbSettingsModel
	{
		return $this->authDbSettingsModel;
	}

	public function getCheckLoginQuery(): string
	{
		return $this->checkLoginQuery;
	}

	public function getWrongPasswordQuery(): ?string
	{
		return $this->wrongPasswordQuery;
	}

	public function getLoadRightsQuery(): ?string
	{
		return $this->loadRightsQuery;
	}

	public function getConfirmLoginQuery(): ?string
	{
		return $this->confirmLoginQuery;
	}

	public function getSaltLength(): int
	{
		return $this->saltLength;
	}

	public function getMaxAllowedWrongPasswordAttempts(): int
	{
		return $this->maxAllowedWrongPasswordAttempts;
	}

	public function getMaxIdleSeconds(): int
	{
		return $this->maxIdleSeconds;
	}

	public function getHashAlgorithm(): string
	{
		return $this->hashAlgorithm;
	}

	public function getLoginPage(): string
	{
		return $this->loginPage;
	}
}