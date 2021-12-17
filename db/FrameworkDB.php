<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\db;

use LogicException;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use stdClass;
use Throwable;

class FrameworkDB extends PDO
{
	private bool $usedTransactions = false;
	private static array $instances = [];

	public static function getInstance(DbSettingsModel $dbSettingsModel): FrameworkDB
	{
		$identifier = $dbSettingsModel->getIdentifier();
		if (isset(FrameworkDB::$instances[$identifier])) {
			return FrameworkDB::$instances[$identifier];
		}

		return FrameworkDB::$instances[$identifier] = new FrameworkDB($dbSettingsModel);
	}

	protected function __construct(DbSettingsModel $dbSettingsModel)
	{
		$identifier = $dbSettingsModel->getIdentifier();
		if (array_key_exists($identifier, FrameworkDB::$instances)) {
			throw new LogicException('It is not allowed to instantiate this class multiple times with the same identifier ' . $identifier);
		}
		FrameworkDB::$instances[$identifier] = $this;

		$initSetCommands = [];
		$timeNamesLanguage = $dbSettingsModel->getTimeNamesLanguage();
		if (!is_null($timeNamesLanguage)) {
			$initSetCommands[] = 'lc_time_names=' . $timeNamesLanguage;
		}

		if ($dbSettingsModel->isSqlSafeUpdates()) {
			// see: https://dev.mysql.com/doc/refman/8.0/en/mysql-tips.html
			$initSetCommands[] = 'sql_safe_updates=1';
		}

		$dsn = implode(';', [
			'mysql:host=' . $dbSettingsModel->getHostName(),
			'dbname=' . $dbSettingsModel->getDatabaseName(),
			'charset=' . $dbSettingsModel->getCharset(),
		]);

		// For following values, please see http://php.net/manual/de/ref.pdo-mysql.php
		$attributeOptions = [
			PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION, // All errors should throw an Exception
			PDO::ATTR_EMULATE_PREPARES => false, // Simulated "prepared statements" are NOT wanted
		];
		if (count($initSetCommands) > 0) {
			$attributeOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET ' . implode(', ', $initSetCommands);
		}
		try {
			parent::__construct($dsn, $dbSettingsModel->getUserName(), $dbSettingsModel->getPassword(), $attributeOptions);
		} catch (Throwable $t) {
			// We do NOT want to leak the database password in the StackTrace of the caught (PDO)Exception.
			throw new PDOException(
				$t->getMessage(),
				$t->getCode()
			);
		}
	}

	/**
	 * Prepares a statement for execution and returns a statement object
	 *
	 * @param string     $query   Valid SQL statement
	 * @param array|null $options One or more key=>value pairs to set attribute values for the returned PDOStatement
	 *
	 * @return PDOStatement
	 */
	public function prepare(string $query, $options = null): PDOStatement
	{
		if (is_null($options)) {
			$options = []; // Necessary circumventing of above mentioned library bug
		}
		try {
			// This (on error) either throws a PDOException OR returns "false", depending on configuration
			$stmt = parent::prepare($query, $options);
			if ($stmt === false) {
				throw new RuntimeException('Could not prepare query.');
			}

			return $stmt;
		} catch (Throwable $throwable) {
			throw new DbRuntimeException(throwable: $throwable, sql: $query);
		}
	}

	/**
	 * Prepares a SELECT statement for repeated execution and returns a special statement object
	 *
	 * @param string $query   : Valid SQL statement
	 * @param null   $options : One or more key=>value pairs to set attribute values for the returned PDOStatement
	 * @param bool   $logQuery
	 *
	 * @return DbSelectStmt
	 * @throws DbRuntimeException
	 */
	public function prepareSelect(string $query, $options = null, bool $logQuery = true): DbSelectStmt
	{
		return new DbSelectStmt($this->prepare($query, $options), $logQuery);
	}

	/**
	 * This method is a shorthand to select data from the database:
	 * "(prepare($sql)->execute($parameters))->fetchAll(PDO::FETCH_OBJ)"
	 *
	 * @param string $sql        valid SQL statement
	 * @param array  $parameters list of parameter values to bind to the prepared sql statement in correct order
	 * @param bool   $logQuery
	 *
	 * @return stdClass[] Array with each row as an object of type stdClass
	 * @throws RuntimeException
	 */
	public function select(string $sql, array $parameters = [], bool $logQuery = false): array
	{
		try {
			if ($logQuery) {
				$dbQueryLogItem = new DbQueryLogItem($sql, $parameters);
			}
			$stmnt = $this->prepare($sql);
			if ($stmnt->execute($parameters) === false) {
				throw new RuntimeException('PDOStatement->execute() returned false');
			}
			$res = $stmnt->fetchAll(PDO::FETCH_OBJ);
			if (isset($dbQueryLogItem)) {
				$dbQueryLogItem->confirmFinishedExecution();
				DbQueryLogList::add($dbQueryLogItem);
			}

			return $res;
		} catch (Throwable $throwable) {
			throw new DbRuntimeException(throwable: $throwable, sql: $sql, parameters: $parameters);
		}
	}

	/**
	 * This method is a shorthand for "(prepare($sql))->execute($parameters)"
	 *
	 * @param string $sql        : valid SQL statement
	 * @param array  $parameters : list of parameter values to bind to the prepared sql statement in correct order
	 * @param bool   $logQuery
	 *
	 * @return PDOStatement : The prepared statement after execution
	 */
	public function execute(string $sql, array $parameters = [], bool $logQuery = false): PDOStatement
	{
		if ($logQuery) {
			$dbQueryLogItem = new DbQueryLogItem($sql, $parameters);
		}
		$stmnt = $this->prepare($sql);
		try {
			if ($stmnt->execute($parameters) === false) {
				throw new RuntimeException('PDOStatement->execute() returned false');
			}

			if (isset($dbQueryLogItem)) {
				$dbQueryLogItem->confirmFinishedExecution();
				DbQueryLogList::add($dbQueryLogItem);
			}

			return $stmnt;
		} catch (Throwable $throwable) {
			throw new DbRuntimeException(throwable: $throwable, sql: $sql, parameters: $parameters);
		}
	}

	public function __destruct()
	{
		if ($this->usedTransactions && $this->inTransaction()) {
			// That error is hard to detect, because in that case PHP silently (!) does a rollback!
			throw new LogicException('An active transaction was not closed properly! Data changes are lost!');
		}
	}

	/**
	 * Begins a transaction
	 *
	 * @return bool : Always returns true, otherwise an Exception because of the severity of the failure
	 * @throws LogicException
	 * @throws RuntimeException
	 */
	public function beginTransaction(): bool
	{
		// Some drivers are mocking about the transaction. We can't tolerate that!
		if ($this->inTransaction()) {
			throw new LogicException('A transaction is already active. Check program code.');
		}
		$r = parent::beginTransaction();
		if (!$r || !$this->inTransaction()) {
			throw new RuntimeException('Could not start transaction. Either error in the underlying driver, or check table engine declaration.');
		}
		$this->usedTransactions = true;

		return true;
	}

	/**
	 * Make changes within a transaction permanent
	 *
	 * @return bool : Always returns true, otherwise an Exception because of the severity of the failure
	 * @throws LogicException
	 * @throws RuntimeException
	 */
	public function commit(): bool
	{
		if (!$this->inTransaction()) {
			throw new LogicException('There was no active transaction! Check program code.');
		}
		if (!parent::commit()) {
			throw new RuntimeException('Could not commit transaction! Withhold data changes are lost!');
		}

		return true;
	}

	/**
	 * Drops any action within a Transaction, thus altering no data
	 *
	 * @return bool : Always returns true, otherwise an Exception because of the severity of the failure
	 * @throws LogicException
	 * @throws RuntimeException
	 */
	public function rollBack(): bool
	{
		if (!$this->inTransaction()) {
			throw new LogicException('There was no active transaction! Check program code.');
		}
		if (!parent::rollBack()) {
			throw new RuntimeException('Could not rollback transaction! Done data changes are permanent!');
		}

		return true;
	}

	/**
	 * Creates a string like "?,?,?,..." for the number of array entries given
	 *
	 * @param array $paramArr
	 *
	 * @return string
	 */
	public function createInQuery(array $paramArr): string
	{
		return implode(',', array_fill(0, count($paramArr), '?'));
	}

	/**
	 * @return DbQueryLogItem[]
	 */
	public function getQueryLog(): array
	{
		return DbQueryLogList::getLog();
	}

	public function lastInsertId($name = null): int
	{
		return (int)parent::lastInsertId($name);
	}
}