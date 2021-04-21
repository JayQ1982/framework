<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use LogicException;
use framework\security\CspPolicySettingsModel;
use framework\session\SessionSettingsModel;

class EnvironmentSettingsModel
{
	private static ?EnvironmentSettingsModel $instance = null;

	private array $allowedDomains;
	private array $availableLanguages;
	private bool $debug;
	private int $copyrightYear;
	private string $timezone;
	private string $logRecipientEmail;
	private string $robots;
	private SessionSettingsModel $sessionSettingsModel;
	private ?CspPolicySettingsModel $cspPolicySettingsModel;

	public function __construct(
		array $allowedDomains,
		array $availableLanguages,
		bool $debug,
		int $copyrightYear,
		string $timezone,
		string $logRecipientEmail,
		string $robots,
		?SessionSettingsModel $sessionSettingsModel,
		?CspPolicySettingsModel $cspPolicySettingsModel
	) {
		if (!is_null(EnvironmentSettingsModel::$instance)) {
			throw new LogicException('There is already an instance of EnvironmentSettingsModel');
		}

		$this->allowedDomains = $allowedDomains;
		$this->availableLanguages = $availableLanguages;
		$this->debug = $debug;
		$this->copyrightYear = $copyrightYear;
		$this->timezone = $timezone;
		$this->logRecipientEmail = $logRecipientEmail;
		$this->robots = $robots;
		$this->sessionSettingsModel = is_null($sessionSettingsModel) ? new SessionSettingsModel() : $sessionSettingsModel;
		$this->cspPolicySettingsModel = $cspPolicySettingsModel;
	}

	public function getAllowedDomains(): array
	{
		return $this->allowedDomains;
	}

	public function getAvailableLanguages(): array
	{
		return $this->availableLanguages;
	}

	public function isDebug(): bool
	{
		return $this->debug;
	}

	public function getCopyrightYear(): int
	{
		return $this->copyrightYear;
	}

	public function getTimezone(): string
	{
		return $this->timezone;
	}

	public function getLogRecipientEmail(): string
	{
		return $this->logRecipientEmail;
	}

	public function getRobots(): string
	{
		return $this->robots;
	}

	public function getSessionSettingsModel(): SessionSettingsModel
	{
		return $this->sessionSettingsModel;
	}

	public function getCspPolicySettingsModel(): ?CspPolicySettingsModel
	{
		return $this->cspPolicySettingsModel;
	}
}