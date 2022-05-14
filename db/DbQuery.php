<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\db;

use framework\table\TableHelper;
use LogicException;
use stdClass;

class DbQuery
{
	private array $selectParts = [];
	private array $fromParts = [];
	private array $whereParts = [];
	private array $orderParts = [];
	private array $parameters;

	private function __construct() { }

	public static function createFromSqlQuery(string $query, array $parameters = []): DbQuery
	{
		$dbQuery = new DbQuery();
		$dbQuery->parameters = $parameters;

		$currentSubQueryLevel = 0;
		$subQueryParts = [];
		$isInSelect = false;
		$isInFrom = false;
		$isInWhere = false;
		foreach (explode(separator: ' ', string: preg_replace(
			pattern: '!\s+!',
			replacement: ' ',
			subject: trim(string: str_replace(search: ['(', ')'], replace: [' ( ', ' ) '], subject: $query))
		)) as $queryPart) {
			if (str_starts_with(haystack: $queryPart, needle: '(') && !str_ends_with(haystack: $queryPart, needle: ')')) {
				if (count(value: $dbQuery->selectParts) === 0 && !$isInSelect) {
					throw new LogicException(message: '( is not allowed before the first SELECT part.');
				}
				$currentSubQueryLevel++;
				$subQueryParts[$currentSubQueryLevel] = [$queryPart];
				continue;
			}
			if ($currentSubQueryLevel > 0) {
				$subQueryParts[$currentSubQueryLevel][] = $queryPart;
				if (!str_ends_with(haystack: $queryPart, needle: ')')) {
					continue;
				}
				$queryPart = implode(separator: ' ', array: $subQueryParts[$currentSubQueryLevel]);
				$currentSubQueryLevel--;
				if ($currentSubQueryLevel > 0) {
					$subQueryParts[$currentSubQueryLevel][] = $queryPart;
					continue;
				}
			} else if (str_ends_with(haystack: $queryPart, needle: ')') && !str_starts_with(haystack: $queryPart, needle: '(')) {
				throw new LogicException(message: ') is not allowed if not part of a sub query.');
			}
			$lowercaseQueryPart = strtolower(string: trim(string: $queryPart));
			if ($lowercaseQueryPart === 'select') {
				if ($isInSelect || $isInFrom || $isInWhere) {
					throw new LogicException(message: '"SELECT" is not allowed if already in "SELECT", "FROM" or "WHERE".');
				}
				$isInSelect = true;
				continue;
			}
			if ($lowercaseQueryPart === 'from') {
				if ($isInFrom || $isInWhere) {
					throw new LogicException(message: '"FROM" is not allowed if already in "FROM" or "WHERE".');
				}
				if (!$isInSelect) {
					throw new LogicException(message: '"FROM" must be after "SELECT".');
				}
				$isInSelect = false;
				$isInFrom = true;
				continue;
			}
			if ($lowercaseQueryPart === 'where') {
				if ($isInWhere) {
					throw new LogicException(message: '"WHERE" is not allowed if already in "WHERE".');
				}
				if (!$isInFrom) {
					throw new LogicException(message: '"WHERE" must be after "FROM".');
				}
				$isInFrom = false;
				$isInWhere = true;
				continue;
			}
			if ($isInSelect) {
				$dbQuery->selectParts[] = $queryPart;
			} else if ($isInFrom) {
				$dbQuery->fromParts[] = $queryPart;
			} else if ($isInWhere) {
				$dbQuery->whereParts[] = $queryPart;
			} else {
				throw new LogicException(message: 'You are not within "SELECT", "FROM" or "WHERE"');
			}
		}

		return $dbQuery;
	}

	/**
	 * @param FrameworkDB $db
	 * @param int         $offset
	 * @param int         $rowCount
	 *
	 * @return stdClass[]
	 */
	public function selectFromDb(FrameworkDB $db, int $offset, int $rowCount): array
	{
		$queryParts = ['SELECT'];
		foreach ($this->selectParts as $part) {
			$queryParts[] = $part;
		}
		$queryParts[] = 'FROM';
		foreach ($this->fromParts as $part) {
			$queryParts[] = $part;
		}
		if (count(value: $this->whereParts) > 0) {
			$queryParts[] = 'WHERE';
			foreach ($this->whereParts as $part) {
				$queryParts[] = $part;
			}
		}
		if (count(value: $this->orderParts) > 0) {
			$queryParts[] = 'ORDER BY ' . implode(separator: ', ', array: $this->orderParts);
		}
		$queryParts[] = 'LIMIT ?, ?';

		$parameters = $this->parameters;
		$parameters[] = $offset;
		$parameters[] = $rowCount;

		return $db->select(
			sql: str_replace(search: ' (', replace: '(', subject: implode(separator: ' ', array: $queryParts)),
			parameters: $parameters
		);
	}

	public function getTotalAmount(FrameworkDB $db): int
	{
		$queryParts = ['SELECT COUNT(*) AS amount'];
		$queryParts[] = 'FROM';
		foreach ($this->fromParts as $part) {
			$queryParts[] = $part;
		}
		if (count(value: $this->whereParts) > 0) {
			$queryParts[] = 'WHERE';
			foreach ($this->whereParts as $part) {
				$queryParts[] = $part;
			}
		}
		$amountOfSelectParameters = count(value: explode(separator: '?', string: implode(separator: ' ', array: $this->selectParts))) - 1;
		$parameters = [];
		$i = 0;
		foreach ($this->parameters as $parameter) {
			$i++;
			if ($i <= $amountOfSelectParameters) {
				continue;
			}
			$parameters[] = $parameter;
		}

		return (int)$db->select(
			sql: str_replace(search: ' (', replace: '(', subject: implode(separator: ' ', array: $queryParts)),
			parameters: $parameters
		)[0]->amount;
	}

	public function addWherePart(string $wherePart, array $parameters): void
	{
		if (count(value: $this->whereParts) > 0) {
			$this->whereParts[] = 'AND';
		}
		$this->whereParts[] = $wherePart;
		foreach ($parameters as $parameter) {
			$this->parameters[] = $parameter;
		}
	}

	public function addOrderPart(string $column, bool $ascending = true): void
	{
		$this->orderParts[] = $column . (!$ascending ? ' ' . TableHelper::SORT_DESC : '');
	}
}