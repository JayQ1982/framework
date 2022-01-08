<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\db;

use framework\datacheck\Sanitizer;
use LogicException;

class DbSettingsModel
{
	private static array $instances = [];

	public const IDX_IDENTIFIER = 'identifier';
	public const IDX_HOSTNAME = 'hostname';
	public const IDX_DATABASE = 'database';
	public const IDX_USERNAME = 'username';
	public const IDX_PASSWORD = 'password';

	private string $identifier;
	private string $hostName;
	private string $databaseName;
	private string $userName;
	private string $password;
	private string $charset;
	private ?string $timeNamesLanguage;
	private bool $sqlSafeUpdates;

	public function __construct(
		string $identifier,
		string $hostName,
		string $databaseName,
		string $userName,
		string $password,
		?string $charset,
		?string $timeNamesLanguage,
		bool $sqlSafeUpdates
	) {
		if (array_key_exists($identifier, DbSettingsModel::$instances)) {
			throw new LogicException('There is already an instance with the identifier ' . $identifier);
		}
		DbSettingsModel::$instances[$identifier] = $this;

		$charset = Sanitizer::trimmedString($charset ?? 'utf8');
		if (mb_strtolower($charset) === 'utf-8') {
			throw new LogicException('Faulty charset setting string "utf-8". Must be "utf8" for PDO driver.');
		}

		$this->identifier = $identifier;
		$this->hostName = $hostName;
		$this->databaseName = $databaseName;
		$this->userName = $userName;
		$this->password = $password;
		$this->charset = $charset;
		$this->timeNamesLanguage = $timeNamesLanguage;
		$this->sqlSafeUpdates = $sqlSafeUpdates;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getHostName(): string
	{
		return $this->hostName;
	}

	public function getDatabaseName(): string
	{
		return $this->databaseName;
	}

	public function getUserName(): string
	{
		return $this->userName;
	}

	public function getPassword(): string
	{
		return $this->password;
	}

	public function getCharset(): string
	{
		return $this->charset;
	}

	public function getTimeNamesLanguage(): ?string
	{
		return $this->timeNamesLanguage;
	}

	public function isSqlSafeUpdates(): bool
	{
		return $this->sqlSafeUpdates;
	}
}