<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\common;

use DateTime;
use framework\core\HttpRequest;
use Throwable;

class SearchHelper
{
	private string $fn;
	private HttpRequest $httpRequest;

	public function __construct(string $fn, HttpRequest $httpRequest)
	{
		$this->fn = $fn;
		$this->httpRequest = $httpRequest;
	}

	public function getBooleanQuery(string $spaceSeparatedFieldNames, string $query_text, $splitFields = true): string
	{
		$clean_query_text = $this->cleanQuery($query_text);

		return "(" . $this->createQuery($clean_query_text, $splitFields, $spaceSeparatedFieldNames) . ")";
	}

	private function cleanQuery($string)
	{

		$string = trim($string);
		$string = strip_tags($string); // remove any html/javascript.
		// prevents duplicate backslashes
		return $string;
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
		mb_internal_encoding('UTF-8');
		$text = mb_strtolower($text);

		#
		# Support +keyword -keyword
		#
		$text = $this->handleShorthand($text);

		#
		# Split, but respect quotation
		#
		$wordarray = $this->explodeRespectQuotes($text);

		$buffer = "";
		$output = "";

		#
		# work through each word (or "quoted phrase") in the text and build the
		# outer shell of the query, filling the insides via createSubquery()
		#
		# "or" is assumed if neither "and" nor "not" is specified
		#
		for ($i = 0; $i < count($wordarray); $i++) {
			$word = $wordarray[$i];

			if (trim($word) == '') {
				continue;
			} else if ($word == "and" || $word == "or" || $word == "not" and $i > 0) {
				if ($word == "not") {
					#
					# $i++ kicks us to the actual keyword that the 'not' is working against, etc
					#
					$i++;
					if ($i == 1) { #invalid sql syntax to prefix the first check with and/or/not
						$buffer = $this->createSubquery($wordarray[$i], "not", $splitFields, $spaceSeparatedFieldNames);
					} else {
						$buffer = " AND " . $this->createSubquery($wordarray[$i], "not", $splitFields, $spaceSeparatedFieldNames);
					}
				} else {
					if ($word == "or") {
						$i++;
						if ($i == 1) {
							$buffer = $this->createSubquery($wordarray[$i], "", $splitFields, $spaceSeparatedFieldNames);
						} else {
							$buffer = " OR " . $this->createSubquery($wordarray[$i], "", $splitFields, $spaceSeparatedFieldNames);
						}
					} else {
						if ($word == "and") {
							$i++;
							if ($i == 1) {
								$buffer = $this->createSubquery($wordarray[$i], "", $splitFields, $spaceSeparatedFieldNames);
							} else {
								$buffer = " AND " . $this->createSubquery($wordarray[$i], "", $splitFields, $spaceSeparatedFieldNames);
							}
						}
					}
				}
			} else {
				if ($i == 0) { # 0 instead of 1 here because there was no conditional word to skip and no $i++;
					$buffer = $this->createSubquery($wordarray[$i], "", $splitFields, $spaceSeparatedFieldNames);
				} else {
					$buffer = " OR " . $this->createSubquery($wordarray[$i], "", $splitFields, $spaceSeparatedFieldNames);
				}
			}
			$output = $output . $buffer;
		}

		return $output;
	}

	private function handleShorthand($text)
	{
		$text = preg_replace("/ \+/", " and ", $text);
		$text = preg_replace("/ -/", " not ", $text);

		return $text;
	}

	/**
	 * Internal function, used to keep quoted text together when building
	 * the query.  i.e.... [fish and chips and "chipped ham"] syntax
	 * It essentially replaces " " with "~~~~" as long as we aren't within
	 * a set of quotes, in which case " " is retained.  The string is then
	 * split on "~~~~~" with the surviving spaces intact.
	 *
	 * @param $line
	 *
	 * @return array
	 */
	private function explodeRespectQuotes($line)
	{
		$quote_level = 0; #keep track if we are in or out of quote-space
		$buffer = "";

		for ($a = 0; $a < strlen($line); $a++) {
			if ($line[$a] == "\"") {
				$quote_level++;
				if ($quote_level == 2) {
					$quote_level = 0;
				}
			} else {

				if ($line[$a] == " " && $quote_level == 0) {
					$buffer = $buffer . "~~~~"; #Hackish magic key
				} else {
					$buffer = $buffer . $line[$a];
				}
			}
		}
		$buffer = str_replace("\\", "", $buffer);

		return explode("~~~~", $buffer);
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
		$word = str_replace("'", "\'", $word);

		if ($mode === "not") {
			$front = "(NOT (";
			$glue = " LIKE '%$word%' OR ";
			$back = " LIKE '%$word%'))";
		} else {
			$front = "(";
			$glue = " LIKE '%$word%' OR ";
			$back = " LIKE '%$word%')";
		}

		$text = ($splitFields) ? str_replace(" ", $glue, $spaceSeparatedFieldNames) : $spaceSeparatedFieldNames;

		return $front . $text . $back;
	}

	public function checkSearchTerm($default = '', $fn = '')
	{
		return $this->checkString('searchterm', $default, $fn);
	}

	public function checkString(string $fieldName, string $default = '', string $fn = ''): string
	{
		$fn = ($fn == '') ? $this->getFN() : $fn;
		if (!isset($_SESSION['search_'.$fn][$fieldName]) || isset($_GET['reset']) || isset($_GET['find'])) {
			$_SESSION['search_'.$fn][$fieldName] = $default;
		}

		$userInput = $this->httpRequest->getInputString($fieldName);
		if (!is_null($userInput)) {
			$_SESSION['search_'.$fn][$fieldName] = $userInput;
		}

		return $_SESSION['search_'.$fn][$fieldName];
	}

	public function getFN()
	{
		return $this->fn;
	}

	public function checkFilter(array $array, string $fieldName, string $default = '', string $fn = ''): string
	{
		$fn = ($fn == '') ? $this->getFN() : $fn;
		if (!isset($_SESSION['search_'.$fn][$fieldName]) || isset($_GET['reset']) || isset($_GET['find'])) {
			$_SESSION['search_'.$fn][$fieldName] = $default;
		}
		$userInput = $this->httpRequest->getInputString($fieldName);
		if (!is_null($userInput) && array_key_exists($userInput, $array)) {
			$_SESSION['search_'.$fn][$fieldName] = $userInput;
		}

		return $_SESSION['search_'.$fn][$fieldName];
	}

	public function checkMultiFilter(array $array, string $fieldName, array $default = [], string $fn = ''): array
	{
		$fn = ($fn == '') ? $this->getFN() : $fn;
		if (!isset($_SESSION['search_'.$fn][$fieldName]) || isset($_GET['reset']) || isset($_GET['find'])) {
			$_SESSION['search_'.$fn][$fieldName] = $default;
		}

		if (isset($_GET['reset']) || isset($_GET['find'])) {
			foreach ($array as $key => $val) {
				$userInput = $this->httpRequest->getInputArray($fieldName);
				if (!is_null($userInput) && in_array($key, $userInput)) {
					$_SESSION['search_'.$fn][$fieldName][] = $key;
				}
			}
			$requestedValue = $this->httpRequest->getInputString($fieldName . 'ID');

			if (!is_null($requestedValue)) {
				$_SESSION['search_'.$fn][$fieldName][] = $requestedValue;
			}
		}

		return $_SESSION['search_'.$fn][$fieldName];
	}

	public function checkDate(string $date): ?DateTime
	{
		if ($date === '') {
			return null;
		}

		try {
			$dateTime = new DateTime($date);
			$dtErrors = DateTime::getLastErrors();
			if ($dtErrors['warning_count'] > 0 || $dtErrors['error_count'] > 0) {
				return null;
			}
		} catch (Throwable $e) {
			return null;
		}

		return $dateTime;
	}

	public function checkDateRangeFilter($dateRange, $fromField, $toField, $fn = '', $defaultFrom = null, $defaultTo = null)
	{

		$fn = ($fn == '') ? $this->getFN() : $fn;

		if (!isset($_SESSION['search_'.$fn][$fromField]) || isset($_GET['reset']) || isset($_GET['find'])) {
			$_SESSION['search_'.$fn][$fromField] = ($defaultFrom === null) ? $dateRange['minDate'] : $defaultFrom;
		}

		if (!isset($_SESSION['search_'.$fn][$toField]) || isset($_GET['reset']) || isset($_GET['find'])) {
			$_SESSION['search_'.$fn][$toField] = ($defaultTo === null) ? $dateRange['maxDate'] : $defaultTo;
		}

		$inputFrom = $this->httpRequest->getInputString($fromField);
		$inputTo = $this->httpRequest->getInputString($toField);

		$dateFromStr = is_null($inputFrom) ? $_SESSION['search_'.$fn][$fromField] : $inputFrom;
		$dateToStr = is_null($inputTo) ? $_SESSION['search_'.$fn][$toField] : $inputTo;

		$dateFromObj = $this->checkDate($dateFromStr);
		$dateToObj = $this->checkDate($dateToStr);

		$minDateObj = new DateTime($dateRange['minDate']);
		$maxDateObj = new DateTime($dateRange['maxDate']);

		// if to ist earlier then from, set it to from
		if (!is_null($dateFromObj) && !is_null($dateToObj) && $dateFromObj->getTimestamp() > $dateToObj->getTimestamp()) {
			$dateToObj = $dateFromObj;
		}

		// if from is empty or earlier than minDate, set it to minDate
		if (is_null($dateFromObj) || $dateFromObj->getTimestamp() < $minDateObj->getTimestamp()) {
			$dateFromObj = $minDateObj;
		}

		// if to is empty or later than maxDate, set it to maxDate
		if (is_null($dateToObj) || $dateToObj->getTimestamp() > $maxDateObj->getTimestamp()) {
			$dateToObj = $maxDateObj;
		}

		$_SESSION['search_'.$fn][$fromField] = $dateFromObj->format('d.m.Y');
		$_SESSION['search_'.$fn][$toField] = $dateToObj->format('d.m.Y');

		return ['dateFrom' => $dateFromObj, 'dateTo' => $dateToObj];
	}

	public function createSQLSearch($string, $columns)
	{
		$searchWords = preg_split("/[\s,]*\"([^\"]+)\"[\s,]*|" . "[\s,]*'([^']+)'[\s,]*|" . "[\s,]+/", $string, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$searchWordsQuery = [];

		foreach ($searchWords as $sw) {
			$swTrim = trim($sw);
			$searchWordsQuery[] = $swTrim;
		}

		$conds = $params = [];
		foreach ($searchWordsQuery as $sw) {
			$condsCol = $paramsArr = [];

			foreach ($columns as $cs) {
				$condsCol[] = "`" . $cs . "` LIKE ?";
				$params[] = '%' . $sw . '%';
			}

			$conds[] = '(' . implode(' OR ', $condsCol) . ')';
		}
		$sql = (count($conds) == 0) ? '' : '(' . implode(' AND ', $conds) . ')';

		return [
			'sql'           => $sql
			, 'params'      => $params
			, 'searchWords' => $searchWords,
		];
	}

	/**
	 * @param array $filterArr : indexed array [ 'colName' => 'colValue', ... ]
	 *
	 * @return array : indexed array [ 'sql' => *, 'params' => * ]
	 */
	public function createSQLFilters(array $filterArr)
	{
		$filterConds = $params = [];

		foreach ($filterArr as $key => $val) {
			$val = trim($val);
			if ($val == '.') {
				$filterConds[] = "" . $key . "!=''";
			} else if ($val == '_') {
				$filterConds[] = "((" . $key . "='') OR (" . $key . " IS NULL))";
			} else {
				$wordsByType['and'] = [];
				$wordsByType['not'] = [];
				$wordsByType['or'] = [];
				$wordsByType['equal'] = [];

				$searchWords = preg_split("/[\s,]*([^\"]+)" . "[\s,]*'([^']+)'[\s,]*|" . "[\s,]+/", $val, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

				foreach ($searchWords as $word) {
					$type = 'or';
					if (strpos($word, '!') === 0 || strpos($word, '-') === 0) {
						$type = 'not';
						$word = substr($word, 1);
					} else if (strpos($word, '+') === 0) {
						$type = 'and';
						$word = substr($word, 1);
					} else if (strpos($word, '"') === 0) {
						$type = 'equal';
						$word = substr($word, 1, -1);
						$wordsByType[$type][] = $word;
						continue;
					}
					$word = str_replace(['*'], ['%'], $word);
					$word = (substr($word, 0, 1) != '%') ? '%' . $word : $word;
					$word = (substr($word, -1) != '%') ? $word . '%' : $word;
					$wordsByType[$type][] = $word;
				}

				foreach ($wordsByType['and'] as $word) {
					$filterConds[] = $key . ' LIKE ?';
					$params[] = $word;
				}

				foreach ($wordsByType['not'] as $word) {
					$filterConds[] = "((" . $key . " NOT LIKE ?) OR " . $key . " IS NULL)";
					$params[] = $word;
				}

				if (count($wordsByType['or']) != 0) {
					$tmpArr = [];
					foreach ($wordsByType['or'] as $word) {
						$tmpArr[] = $key . ' LIKE ?';
						$params[] = $word;
					}
					$filterConds[] = '(' . implode(' OR ', $tmpArr) . ')';
				}

				foreach ($wordsByType['equal'] as $word) {
					$filterConds[] = $key . ' = ?';
					$params[] = $word;
				}
			}
		}

		return [
			'sql'    => implode(' AND ', $filterConds),
			'params' => $params,
		];
	}
}
/* EOF */