<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

use DateTime;
use framework\core\HttpRequest;
use framework\db\DbQueryData;
use Throwable;

class SearchHelper
{
	public const PARAM_RESET = 'reset';
	public const PARAM_FIND = 'find';

	private string $sessionRootName = 'searchHelper';
	private string $instanceName;
	/** @var SearchHelper[] */
	private static array $instances = [];

	public static function getInstance(string $instanceName): SearchHelper
	{
		if (!array_key_exists(key: $instanceName, array: SearchHelper::$instances)) {
			SearchHelper::$instances[$instanceName] = new SearchHelper(instanceName: $instanceName);
		}

		return SearchHelper::$instances[$instanceName];
	}

	private function __construct(string $instanceName)
	{
		$this->instanceName = $instanceName;
	}

	public function getBooleanQuery(string $spaceSeparatedFieldNames, string $query_text, $splitFields = true): string
	{
		$clean_query_text = $this->cleanQuery(string: $query_text);

		return "(" . $this->createQuery(
				text: $clean_query_text,
				splitFields: $splitFields,
				spaceSeparatedFieldNames: $spaceSeparatedFieldNames
			) . ")";
	}

	private function cleanQuery(string $string): string
	{
		return strip_tags(string: trim(string: $string));
	}

	/**
	 * Generates the "WHERE" portion of a query, iterating over every key phrase in the
	 * given search string.  Is safe to link (e.g. with AND) with the output of repeated
	 * calls
	 *
	 * @param string $text
	 * @param bool   $splitFields
	 * @param string $spaceSeparatedFieldNames
	 *
	 * @return string
	 */
	private function createQuery(string $text, bool $splitFields, string $spaceSeparatedFieldNames): string
	{
		#
		# We can't trust the user to give us a specific case
		#
		mb_internal_encoding(encoding: 'UTF-8');
		$text = mb_strtolower(string: $text);

		#
		# Support +keyword -keyword
		#
		$text = $this->handleShorthand(text: $text);

		#
		# Split, but respect quotation
		#
		$wordArray = $this->explodeRespectQuotes(line: $text);

		$buffer = "";
		$output = "";

		#
		# work through each word (or "quoted phrase") in the text and build the
		# outer shell of the query, filling the insides via createSubquery()
		#
		# "or" is assumed if neither "and" nor "not" is specified
		#
		for ($i = 0; $i < count(value: $wordArray); $i++) {
			$word = trim(string: $wordArray[$i]);

			if ($word === '') {
				continue;
			}
			if ($word === 'and' || $word === 'or' || $word === 'not' and $i > 0) {
				if ($word === 'not') {
					#
					# $i++ kicks us to the actual keyword that the 'not' is working against, etc
					#
					$i++;
					if ($i === 1) { #invalid sql syntax to prefix the first check with and/or/not
						$buffer = $this->createSubquery(
							word: $wordArray[$i],
							mode: 'not',
							splitFields: $splitFields,
							spaceSeparatedFieldNames: $spaceSeparatedFieldNames
						);
					} else {
						$buffer = ' AND ' . $this->createSubquery(
								word: $wordArray[$i],
								mode: 'not',
								splitFields: $splitFields,
								spaceSeparatedFieldNames: $spaceSeparatedFieldNames
							);
					}
				} else {
					if ($word === 'or') {
						$i++;
						if ($i == 1) {
							$buffer = $this->createSubquery(
								word: $wordArray[$i],
								mode: '',
								splitFields: $splitFields,
								spaceSeparatedFieldNames: $spaceSeparatedFieldNames
							);
						} else {
							$buffer = ' OR ' . $this->createSubquery(
									word: $wordArray[$i],
									mode: '',
									splitFields: $splitFields,
									spaceSeparatedFieldNames: $spaceSeparatedFieldNames
								);
						}
					} else {
						if ($word === 'and') {
							$i++;
							if ($i == 1) {
								$buffer = $this->createSubquery(
									word: $wordArray[$i],
									mode: '',
									splitFields: $splitFields,
									spaceSeparatedFieldNames: $spaceSeparatedFieldNames
								);
							} else {
								$buffer = ' AND ' . $this->createSubquery(
										word: $wordArray[$i],
										mode: '',
										splitFields: $splitFields,
										spaceSeparatedFieldNames: $spaceSeparatedFieldNames
									);
							}
						}
					}
				}
			} else {
				if ($i == 0) { # 0 instead of 1 here because there was no conditional word to skip and no $i++;
					$buffer = $this->createSubquery(
						word: $wordArray[$i],
						mode: '',
						splitFields: $splitFields,
						spaceSeparatedFieldNames: $spaceSeparatedFieldNames
					);
				} else {
					$buffer = ' OR ' . $this->createSubquery(
							word: $wordArray[$i],
							mode: '',
							splitFields: $splitFields,
							spaceSeparatedFieldNames: $spaceSeparatedFieldNames
						);
				}
			}
			$output = $output . $buffer;
		}

		return $output;
	}

	private function handleShorthand(string $text): string
	{
		$text = preg_replace(
			pattern: '/ \+/',
			replacement: ' and ',
			subject: $text
		);

		return preg_replace(
			pattern: '/ -/',
			replacement: ' not ',
			subject: $text
		);
	}

	/**
	 * Internal function, used to keep quoted text together when building
	 * the query.  i.e.... [fish and chips and "chipped ham"] syntax
	 * It essentially replaces " " with "~~~~" as long as we aren't within
	 * a set of quotes, in which case " " is retained.  The string is then
	 * split on "~~~~~" with the surviving spaces intact.
	 *
	 * @param string $line
	 *
	 * @return string[]
	 */
	private function explodeRespectQuotes(string $line): array
	{
		$quote_level = 0; #keep track if we are in or out of quote-space
		$buffer = '';

		for ($a = 0; $a < strlen(string: $line); $a++) {
			if ($line[$a] == "\"") {
				$quote_level++;
				if ($quote_level === 2) {
					$quote_level = 0;
				}
			} else {

				if ($line[$a] === ' ' && $quote_level === 0) {
					$buffer = $buffer . "~~~~"; #Hackish magic key
				} else {
					$buffer = $buffer . $line[$a];
				}
			}
		}
		$buffer = str_replace(search: "\\", replace: '', subject: $buffer);

		return explode(separator: '~~~~', string: $buffer);
	}

	/**
	 * Internal function, used to apply a single keyword against an
	 * arbitrary number of fields in the database in the same fashion.
	 * Works via replacing whitespace rather than iteration
	 *
	 * @param string $word
	 * @param string $mode
	 * @param bool   $splitFields
	 * @param string $spaceSeparatedFieldNames
	 *
	 * @return string
	 */
	private function createSubquery(string $word, string $mode, bool $splitFields, string $spaceSeparatedFieldNames): string
	{
		$word = str_replace(search: "'", replace: "\'", subject: $word);

		if ($mode === 'not') {
			$front = '(NOT (';
			$glue = " LIKE '%$word%' OR ";
			$back = " LIKE '%$word%'))";
		} else {
			$front = '(';
			$glue = " LIKE '%$word%' OR ";
			$back = " LIKE '%$word%')";
		}

		$text = ($splitFields) ? str_replace(search: ' ', replace: $glue, subject: $spaceSeparatedFieldNames) : $spaceSeparatedFieldNames;

		return $front . $text . $back;
	}

	public function checkSearchTerm(string $default = ''): string
	{
		return $this->checkString(fieldName: 'searchterm', default: $default);
	}

	private function resetField(string $fieldName, string $default = ''): void
	{
		$sessionRootName = $this->sessionRootName;
		$instanceName = $this->instanceName;
		if (
			!isset($_SESSION[$sessionRootName][$instanceName][$fieldName])
			|| !is_null(HttpRequest::getInputString(keyName: SearchHelper::PARAM_RESET))
			|| !is_null(HttpRequest::getInputString(keyName: SearchHelper::PARAM_FIND))
		) {
			$_SESSION[$sessionRootName][$instanceName][$fieldName] = $default;
		}
	}

	public function checkString(string $fieldName, string $default = ''): string
	{
		$sessionRootName = $this->sessionRootName;
		$instanceName = $this->instanceName;
		$this->resetField(fieldName: $fieldName, default: $default);

		$userInput = HttpRequest::getInputString(keyName: $fieldName);
		if (!is_null($userInput)) {
			$_SESSION[$sessionRootName][$instanceName][$fieldName] = $userInput;
		}

		return $_SESSION[$sessionRootName][$instanceName][$fieldName];
	}

	public function checkFilter(array $array, string $fieldName, string $default = ''): string
	{
		$sessionRootName = $this->sessionRootName;
		$instanceName = $this->instanceName;
		$this->resetField(fieldName: $fieldName, default: $default);

		$userInput = HttpRequest::getInputString(keyName: $fieldName);
		if (!is_null($userInput) && array_key_exists($userInput, $array)) {
			$_SESSION[$sessionRootName][$instanceName][$fieldName] = $userInput;
		}

		return $_SESSION[$sessionRootName][$instanceName][$fieldName];
	}

	public function checkMultiFilter(array $array, string $fieldName, array $default = []): array
	{
		$instanceName = $this->instanceName;
		if (!isset($_SESSION[$this->sessionRootName][$instanceName][$fieldName]) || isset($_GET[SearchHelper::PARAM_RESET]) || isset($_GET[SearchHelper::PARAM_FIND])) {
			$_SESSION[$this->sessionRootName][$instanceName][$fieldName] = $default;
		}

		if (isset($_GET[SearchHelper::PARAM_RESET]) || isset($_GET[SearchHelper::PARAM_FIND])) {
			foreach ($array as $key => $val) {
				$userInput = HttpRequest::getInputArray(keyName: $fieldName);
				if (!is_null($userInput) && in_array(needle: $key, haystack: $userInput)) {
					$_SESSION[$this->sessionRootName][$instanceName][$fieldName][] = $key;
				}
			}
			$requestedValue = HttpRequest::getInputString(keyName: $fieldName . 'ID');

			if (!is_null(value: $requestedValue)) {
				$_SESSION[$this->sessionRootName][$instanceName][$fieldName][] = $requestedValue;
			}
		}

		return $_SESSION[$this->sessionRootName][$instanceName][$fieldName];
	}

	public function checkDate(string $date): ?DateTime
	{
		if ($date === '') {
			return null;
		}
		try {
			$dateTime = new DateTime(datetime: $date);
			if (DateTime::getLastErrors() !== false) {
				return null;
			}

			return $dateTime;
		} catch (Throwable) {
			return null;
		}
	}

	public function checkDateRangeFilter(array $dateRange, string $fromField, string $toField, ?string $defaultFrom = null, ?string $defaultTo = null): array
	{
		$instanceName = $this->instanceName;

		if (!isset($_SESSION[$this->sessionRootName][$instanceName][$fromField]) || isset($_GET[SearchHelper::PARAM_RESET]) || isset($_GET[SearchHelper::PARAM_FIND])) {
			$_SESSION[$this->sessionRootName][$instanceName][$fromField] = ($defaultFrom === null) ? $dateRange['minDate'] : $defaultFrom;
		}

		if (!isset($_SESSION[$this->sessionRootName][$instanceName][$toField]) || isset($_GET[SearchHelper::PARAM_RESET]) || isset($_GET[SearchHelper::PARAM_FIND])) {
			$_SESSION[$this->sessionRootName][$instanceName][$toField] = ($defaultTo === null) ? $dateRange['maxDate'] : $defaultTo;
		}

		$inputFrom = HttpRequest::getInputString(keyName: $fromField);
		$inputTo = HttpRequest::getInputString(keyName: $toField);

		$dateFromStr = is_null($inputFrom) ? $_SESSION[$this->sessionRootName][$instanceName][$fromField] : $inputFrom;
		$dateToStr = is_null($inputTo) ? $_SESSION[$this->sessionRootName][$instanceName][$toField] : $inputTo;

		$dateFromObj = $this->checkDate(date: $dateFromStr);
		$dateToObj = $this->checkDate(date: $dateToStr);

		$minDateObj = new DateTime(datetime: $dateRange['minDate']);
		$maxDateObj = new DateTime(datetime: $dateRange['maxDate']);

		// if to ist earlier then from, set it to from
		if (!is_null(value: $dateFromObj) && !is_null(value: $dateToObj) && $dateFromObj->getTimestamp() > $dateToObj->getTimestamp()) {
			$dateToObj = $dateFromObj;
		}

		// if from is empty or earlier than minDate, set it to minDate
		if (is_null(value: $dateFromObj) || $dateFromObj->getTimestamp() < $minDateObj->getTimestamp()) {
			$dateFromObj = $minDateObj;
		}

		// if to is empty or later than maxDate, set it to maxDate
		if (is_null(value: $dateToObj) || $dateToObj->getTimestamp() > $maxDateObj->getTimestamp()) {
			$dateToObj = $maxDateObj;
		}

		$_SESSION[$this->sessionRootName][$instanceName][$fromField] = $dateFromObj->format(format: 'd.m.Y');
		$_SESSION[$this->sessionRootName][$instanceName][$toField] = $dateToObj->format(format: 'd.m.Y');

		return ['dateFrom' => $dateFromObj, 'dateTo' => $dateToObj];
	}

	public function createSQLSearch(string $string, array $columns): array
	{
		$searchWords = preg_split(
			pattern: "/[\s,]*\"([^\"]+)\"[\s,]*|" . "[\s,]*'([^']+)'[\s,]*|" . "[\s,]+/",
			subject: $string,
			limit: -1,
			flags: PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
		);
		$searchWordsQuery = [];

		foreach ($searchWords as $sw) {
			$searchWordsQuery[] = trim(string: $sw);
		}

		$conds = $params = [];
		foreach ($searchWordsQuery as $sw) {
			$condsCol = [];

			foreach ($columns as $cs) {
				$condsCol[] = "`" . $cs . "` LIKE ?";
				$params[] = '%' . $sw . '%';
			}

			$conds[] = '(' . implode(separator: ' OR ', array: $condsCol) . ')';
		}
		$sql = (count(value: $conds) == 0) ? '' : '(' . implode(separator: ' AND ', array: $conds) . ')';

		return [
			'sql'           => $sql
			, 'params'      => $params
			, 'searchWords' => $searchWords,
		];
	}

	/**
	 * @param array $filterArr : indexed array [ 'colName' => 'colValue', ... ]
	 *
	 * @return DbQueryData
	 */
	public static function createSQLFilters(array $filterArr): DbQueryData
	{
		$whereConditions = [];
		$sqlParams = [];

		foreach ($filterArr as $dataTableReference => $value) {
			$dataTableReference = trim(string: $dataTableReference);
			$value = trim(string: (string)$value);
			if ($value === '') {
				continue;
			}
			if ($value === '.') {
				$whereConditions[] = '(' . $dataTableReference . '!=\'\' AND ' . $dataTableReference . ' IS NOT NULL)';
				continue;
			}
			if ($value === '_') {
				$whereConditions[] = '((' . $dataTableReference . '=\'\') OR (' . $dataTableReference . ' IS NULL))';
				continue;
			}
			if (
				mb_strlen(string: $value) > 2
				&& substr_count(haystack: $value, needle: '"') === 2
				&& str_starts_with(haystack: $value, needle: '"')
				&& str_ends_with(haystack: $value, needle: '"')
			) {
				$whereConditions[] = $dataTableReference . '=?';
				$sqlParams[] = substr(string: $value, offset: 1, length: -1);
				continue;
			}
			$strToCheck = str_replace(search: '*', replace: '%', subject: $value);
			if (
				mb_strlen(string: $strToCheck) > 2
				&& substr_count(haystack: $strToCheck, needle: '%') === 2
				&& str_starts_with(haystack: $strToCheck, needle: '%')
				&& str_ends_with(haystack: $strToCheck, needle: '%')
			) {
				$whereConditions[] = $dataTableReference . ' LIKE ?';
				$sqlParams[] = $strToCheck;
				continue;
			}
			$searchWords = preg_split(
				pattern: "/[\s,]*([^\"]+)" . "[\s,]*'([^']+)'[\s,]*|" . "[\s,]+/",
				subject: $value,
				limit: -1,
				flags: PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
			);
			$remainingLazySearchWords = [];
			foreach ($searchWords as $word) {
				if (
					str_starts_with(haystack: $word, needle: '!')
					|| str_starts_with(haystack: $word, needle: '-')
				) {
					$word = substr(string: $word, offset: 1);
					$whereConditions[] = "((" . $dataTableReference . " NOT LIKE ?) OR " . $dataTableReference . " IS NULL)";
					$sqlParams[] = SearchHelper::addWildcardToString(string: $word);
					continue;
				}
				if (str_starts_with(haystack: $word, needle: '+')) {
					$word = substr(string: $word, offset: 1);
					$whereConditions[] = $dataTableReference . ' LIKE ?';
					$sqlParams[] = SearchHelper::addWildcardToString(string: $word);
					continue;
				}
				if (
					str_starts_with(haystack: $word, needle: '"')
					&& str_ends_with(haystack: $word, needle: '"')
					&& mb_strlen(string: $word) > 2
				) {
					$whereConditions[] = $dataTableReference . '=?';
					$sqlParams[] = substr(string: $word, offset: 1, length: -1);
					continue;
				}
				$remainingLazySearchWords[] = $word;
			}
			if (count(value: $remainingLazySearchWords) > 0) {
				$tmpArr = [];
				foreach ($remainingLazySearchWords as $word) {
					$tmpArr[] = $dataTableReference . ' LIKE ?';
					$sqlParams[] = SearchHelper::addWildcardToString(string: $word);
				}
				$whereConditions[] = '(' . implode(separator: ' OR ', array: $tmpArr) . ')';
			}
		}
		if (count(value: $whereConditions) === 0) {
			$whereConditions[] = '1=1';
		}

		return new DbQueryData(query: implode(separator: ' AND ', array: $whereConditions), params: $sqlParams);
	}

	public static function addWildcardToString(string $string): string
	{
		$string = str_replace(search: ['*'], replace: ['%'], subject: $string);
		$string = !str_starts_with(haystack: $string, needle: '%') ? '%' . $string : $string;

		return !str_ends_with(haystack: $string, needle: '%') ? $string . '%' : $string;
	}
}