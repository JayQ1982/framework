<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\db;

use framework\core\EnvironmentHandler;
use LogicException;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use stdClass;
use Throwable;

final class FrameworkDB extends PDO
{
	private bool $usedTransactions = false;
	private static array $instances = [];
	/** @var DbQueryLogItem[] */
	private array $queryLog = [];

	public static function getInstance(EnvironmentHandler $environmentHandler, string $clientLanguage, string $id = 'default'): FrameworkDB
	{
		if (isset(FrameworkDB::$instances[$id])) {
			return FrameworkDB::$instances[$id];
		}

		return FrameworkDB::$instances[$id] = new FrameworkDB($environmentHandler, $clientLanguage, $id);
	}

	private function __construct(EnvironmentHandler $environmentHandler, string $clientLanguage, string $id)
	{
		$initSetCommands = [];
		$availableLanguages = $environmentHandler->getAvailableLanguages();

		if (isset($availableLanguages[$clientLanguage])) {
			$initSetCommands[] = 'lc_time_names=' . $availableLanguages[$clientLanguage];
		}

		if ($environmentHandler->isDebug()) {
			// see: https://dev.mysql.com/doc/refman/8.0/en/mysql-tips.html
			$initSetCommands[] = 'sql_safe_updates=1';
		}

		$dbSettingsModel = DbSettingsModel::getByID($environmentHandler, $id);

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

		parent::__construct($dsn, $dbSettingsModel->getUserName(), $dbSettingsModel->getPassword(), $attributeOptions);
	}

	/**
	 * Prepares a statement for execution and returns a statement object
	 *
	 * @param string     $query   : Valid SQL statement
	 * @param array|null $options : One or more key=>value pairs to set attribute values for the returned PDOStatement
	 *
	 * @return PDOStatement
	 * @noinspection PhpMissingParamTypeInspection : Declaration is OKAY! Ignore PHPStorm warning (= bug in it's library files)!
	 */
	public function prepare($query, $options = null): PDOStatement
	{
		if (is_null($options)) {
			$options = []; // Necessary circumventing of above mentioned library bug
		}
		try {
			// This (on error) either throws a PDOException OR returns "false", depending on configuration
			$stmnt = parent::prepare($query, $options);
			if ($stmnt === false) {
				throw new RuntimeException('Could not prepare query.');
			}

			return $stmnt;
		} catch (Throwable $t) {
			throw $this->getEnrichedException($t, $query);
		}
	}

	/**
	 * This method is a shorthand to select data from the database:
	 * "(prepare($sql)->execute($parameters))->fetchAll(PDO::FETCH_OBJ)"
	 *
	 * @param string $sql        : valid SQL statement
	 * @param array  $parameters : list of parameter values to bind to the prepared sql statement in correct order
	 *
	 * @return stdClass[]: Array with each row as an object of type stdClass
	 * @throws RuntimeException
	 */
	public function select(string $sql, array $parameters = []): array
	{
		try {
			$dbQueryLogItem = new DbQueryLogItem($sql, $parameters);
			$stmnt = $this->prepare($sql);
			if ($stmnt->execute($parameters) === false) {
				throw new RuntimeException('PDOStatement->execute() returned false');
			}
			$res = $stmnt->fetchAll(PDO::FETCH_OBJ);
			$dbQueryLogItem->confirmFinishedExecution();
			$this->queryLog[] = $dbQueryLogItem;

			return $res;
		} catch (Throwable $t) {
			throw $this->getEnrichedException($t, $sql, $parameters);
		}
	}

	/**
	 * This method is a shorthand for "(prepare($sql))->execute($parameters)"
	 *
	 * @param string $sql        : valid SQL statement
	 * @param array  $parameters : list of parameter values to bind to the prepared sql statement in correct order
	 *
	 * @return PDOStatement : The prepared statement after execution
	 */
	public function execute(string $sql, array $parameters = []): PDOStatement
	{
		$dbQueryLogItem = new DbQueryLogItem($sql, $parameters);
		$stmnt = $this->prepare($sql);
		try {
			if ($stmnt->execute($parameters) === false) {
				throw new RuntimeException('PDOStatement->execute() returned false');
			}

			$dbQueryLogItem->confirmFinishedExecution();
			$this->queryLog[] = $dbQueryLogItem;

			return $stmnt;
		} catch (Throwable $t) {
			throw $this->getEnrichedException($t, $sql, $parameters);
		}
	}

	/**
	 * Internal method to enrich an Throwable with some more useful information about
	 * the actual SQL statement, and determining the real Error code from an PDOException.
	 * This Method should NOT be chained, thus its thrown Exception not be caught to pass
	 * again through this method. ;-)
	 *
	 * @param Throwable $t
	 * @param string    $sql
	 * @param array     $parameters
	 *
	 * @return RuntimeException
	 */
	private function getEnrichedException(Throwable $t, string $sql, array $parameters = []): RuntimeException
	{
		$realCode = $t->getCode();
		if ($t instanceof PDOException) {
			$realCode = (isset($t->errorInfo) && is_array($t->errorInfo) && isset($t->errorInfo[1])) ? $t->errorInfo[1] : 0;
		}

		$msg = 'PDO could not execute statement: ' . $t->getMessage() . ';' . PHP_EOL . 'SQL string was: "' . $sql . '"';
		if (count($parameters) != 0) {
			$msg .= ';' . PHP_EOL . 'Parameters where ["' . implode('","', $parameters) . '""]';
		}

		return new RuntimeException($msg, $realCode, $t);
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
	 * @param $paramArr
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
		return $this->queryLog;
	}
}