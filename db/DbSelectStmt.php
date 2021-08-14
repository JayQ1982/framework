<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\db;

use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

class DbSelectStmt
{
	private PDOStatement $pdoStatement;
	private bool $logQuery;

	public function __construct(PDOStatement $pdoStatement, bool $logQuery = true)
	{
		$this->pdoStatement = $pdoStatement;
		$this->logQuery = $logQuery;
	}

	// The classic PDOStatement has execute() and fetch(). It was desired to mimic the
	// effect of FrameworkDB->select(), which does both on ONE call.
	public function ExecuteAndFetch(array $parameters): array
	{
		try {
			if ($this->logQuery) {
				$dbQueryLogItem = new DbQueryLogItem($this->pdoStatement->queryString, $parameters);
			}
			if ($this->pdoStatement->execute($parameters) === false) {
				throw new RuntimeException('PDOStatement->execute() returned false');
			}
			$res = $this->pdoStatement->fetchAll(PDO::FETCH_OBJ);
			if (isset($dbQueryLogItem)) {
				$dbQueryLogItem->confirmFinishedExecution();
				DbQueryLogList::add($dbQueryLogItem);
			}

			return $res;
		} catch (Throwable $t) {
			throw new DbRuntimeException($t, $this->pdoStatement->queryString, $parameters);
		}
	}
}