<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\session;

class SessionSettingsModel
{
	private ?string $savePath;
	private ?string $individualName;
	private ?int $maxLifeTime;
	private bool $useNoSqlSessionHandler;
	private bool $isSameSiteStrict;

	public function __construct(
		?string $savePath = null,
		?string $individualName = null,
		?int $maxLifeTime = null,
		bool $useNoSqlSessionHandler = false,
		bool $isSameSiteStrict = true
	) {
		$this->savePath = $savePath;
		$this->individualName = $individualName;
		$this->maxLifeTime = $maxLifeTime;
		$this->useNoSqlSessionHandler = $useNoSqlSessionHandler;
		$this->isSameSiteStrict = $isSameSiteStrict;
	}

	public function getSavePath(): ?string
	{
		return $this->savePath;
	}

	public function getIndividualName(): ?string
	{
		return $this->individualName;
	}

	public function getMaxLifeTime(): ?int
	{
		return $this->maxLifeTime;
	}

	public function isUseNoSqlSessionHandler(): bool
	{
		return $this->useNoSqlSessionHandler;
	}

	public function isSameSiteStrict(): bool
	{
		return $this->isSameSiteStrict;
	}
}