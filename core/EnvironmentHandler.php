<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use stdClass;

class EnvironmentHandler
{
	private bool $debug;
	private string $logRecipientEmail;
	private string $timezone;
	private array $allowedDomains;
	private array $availableLanguages;
	private ?string $sessionSavePath;
	private ?string $sessionName;
	private ?int $sessionMaxLifeTime;
	private string $defaultPageAfterLogin;
	private string $defaultAccessDeniedPage;
	private int $copyrightYear;
	private string $robots;
	private stdClass $cspPolicySettings;
	private stdClass $dbCredentials;
	private stdClass $apiTargets;
	private ?string $defaultFromEmail;
	private ?string $defaultFromName;
	private ?string $defaultReplyToEmail;
	private ?string $defaultReplyToName;

	public function __construct(SettingsHandler $settingsHandler)
	{
		$envSettings = $settingsHandler->get('envSettings');
		$coreSettings = $settingsHandler->get('core');

		$this->debug = $envSettings->debug ?? false;
		$this->logRecipientEmail = $envSettings->logRecipientEmail ?? $coreSettings->logRecipientEmail;
		$this->timezone = $envSettings->timezone ?? $coreSettings->timezone;
		$this->allowedDomains = $envSettings->allowedDomains ?? [];
		$this->availableLanguages = (array)($envSettings->availableLanguages ?? $coreSettings->availableLanguages);
		$this->sessionSavePath = $envSettings->sessionSavePath ?? $coreSettings->sessionSavePath ?? null;
		$this->sessionName = $envSettings->sessionName ?? $coreSettings->sessionName ?? null;
		$this->sessionMaxLifeTime = $envSettings->sessionMaxLifeTime ?? $coreSettings->sessionMaxLifeTime ?? null;
		$this->defaultPageAfterLogin = $coreSettings->defaultPageAfterLogin ?? '/';
		$this->defaultAccessDeniedPage = $coreSettings->defaultAccessDeniedPage ?? '/';
		$this->copyrightYear = $coreSettings->copyrightYear ?? date('Y');
		$this->robots = $envSettings->robots ?? $coreSettings->robots;
		$this->cspPolicySettings = $envSettings->csp ?? $coreSettings->csp;
		$this->dbCredentials = $envSettings->dbCredentials ?? new stdClass();
		$this->apiTargets = $envSettings->apiTargets ?? new stdClass();
		$this->defaultFromEmail = $envSettings->defaultFromEmail ?? $coreSettings->defaultFromEmail ?? null;
		$this->defaultFromName = $envSettings->defaultFromName ?? $coreSettings->defaultFromName ?? null;
		$this->defaultReplyToEmail = $envSettings->defaultReplyToEmail ?? $coreSettings->defaultReplyToEmail ?? null;
		$this->defaultReplyToName = $envSettings->defaultReplyToName ?? $coreSettings->defaultReplyToName ?? null;
	}

	public function isDebug(): bool
	{
		return $this->debug;
	}

	public function getLogRecipientEmail(): string
	{
		return $this->logRecipientEmail;
	}

	public function getTimezone(): string
	{
		return $this->timezone;
	}

	public function getAllowedDomains(): array
	{
		return $this->allowedDomains;
	}

	public function getAvailableLanguages(): array
	{
		return $this->availableLanguages;
	}

	public function getSessionSavePath(): ?string
	{
		return $this->sessionSavePath;
	}

	public function getSessionName(): ?string
	{
		return $this->sessionName;
	}

	public function getSessionMaxLifeTime(): ?int
	{
		return $this->sessionMaxLifeTime;
	}

	public function getDefaultPageAfterLogin(): string
	{
		return $this->defaultPageAfterLogin;
	}

	public function getDefaultAccessDeniedPage(): string
	{
		return $this->defaultAccessDeniedPage;
	}

	public function setDefaultAccessDeniedPage(string $defaultAccessDeniedPage): void
	{
		$this->defaultAccessDeniedPage = $defaultAccessDeniedPage;
	}

	public function getCopyrightYear(): int
	{
		return $this->copyrightYear;
	}

	public function getRobots(): string
	{
		return $this->robots;
	}

	public function getCspPolicySettings(): stdClass
	{
		return $this->cspPolicySettings;
	}

	public function getDbCredentials(): stdClass
	{
		return $this->dbCredentials;
	}

	public function getApiTargets(): stdClass
	{
		return $this->apiTargets;
	}

	public function getDefaultFromEmail(): ?string
	{
		return $this->defaultFromEmail;
	}

	public function getDefaultFromName(): ?string
	{
		return $this->defaultFromName;
	}

	public function getDefaultReplyToEmail(): ?string
	{
		return $this->defaultReplyToEmail;
	}

	public function getDefaultReplyToName(): ?string
	{
		return $this->defaultReplyToName;
	}
}
/* EOF */