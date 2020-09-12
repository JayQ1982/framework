<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\db;

use framework\core\EnvironmentHandler;
use RuntimeException;

class DbSettingsModel
{
	private string $charset;
	private string $hostName;
	private string $databaseName;
	private string $userName;
	private string $password;

	public static function getByID(EnvironmentHandler $environmentHandler, string $id): DbSettingsModel
	{
		$dbList = $environmentHandler->getDbCredentials();
		$data = $dbList->{$id};

		return new DbSettingsModel(
			$data->host,
			$data->database,
			$data->username,
			$data->password,
			$data->charset ?? null
		);
	}

	private function __construct(string $hostName, string $databaseName, string $userName, string $password, ?string $charset)
	{
		$charset = trim($charset);

		if (empty($charset)) {
			$charset = 'utf8'; // UTF-8 is default
		} else if (strtolower($charset) === 'utf-8') {
			throw new RuntimeException('Faulty charset setting string "utf-8". Must be "utf8" for PDO driver.');
		}

		$this->charset = $charset;
		$this->hostName = $hostName;
		$this->databaseName = $databaseName;
		$this->userName = $userName;
		$this->password = $password;
	}

	public function getCharset(): string
	{
		return $this->charset;
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
}
/* EOF */