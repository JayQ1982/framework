<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\db;

use LogicException;

class DbSettingsModel
{
	private static array $instances = [];

	public const IDX_IDENTIFIER = 'identifier';
	public const IDX_HOSTNAME = 'hostname';
	public const IDX_DATABASE = 'database';
	public const IDX_USERNAME = 'username';
	public const IDX_PASSWORD = 'password';

	public function __construct(
		private         readonly string $identifier,
		private         readonly string $hostName,
		private         readonly string $databaseName,
		private         readonly string $userName,
		private         readonly string $password,
		private ?string $charset,
		private         readonly ?string $timeNamesLanguage,
		private         readonly bool $sqlSafeUpdates
	) {
		if (array_key_exists(key: $identifier, array: DbSettingsModel::$instances)) {
			throw new LogicException(message: 'There is already an instance with the identifier ' . $identifier);
		}
		DbSettingsModel::$instances[$identifier] = $this;

		$this->charset = trim(string: (string)$this->charset);
		if ($this->charset === '') {
			$this->charset = 'utf8';
		}
		if (mb_strtolower(string: $this->charset) === 'utf-8') {
			throw new LogicException(message: 'Faulty charset setting string "utf-8". Must be "utf8" for PDO driver.');
		}
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