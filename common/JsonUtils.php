<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\common;

use Exception;
use stdClass;

class JsonUtils
{
	/**
	 * Converts a JSON string into a nested array.
	 *
	 * @param      $string : The JSON string
	 * @param bool $assoc  : Return associative array (or json object if false)
	 *
	 * @return array|stdClass|null : The nested array on success, "null" on failure/error
	 */
	public static function deJson($string, bool $assoc = true)
	{
		return json_decode($string, $assoc, 512, JSON_BIGINT_AS_STRING);
	}

	/**
	 * Converts a nested array into a JSON string.
	 *
	 * @param array $array : A nested array
	 *
	 * @return string|bool : The JSON string on success, "false" on failure/error
	 */
	public static function enJson(array $array)
	{
		return json_encode($array, (JSON_UNESCAPED_UNICODE | JSON_BIGINT_AS_STRING), 512);
	}

	public static function decode(string $json, bool $toAssoc = false, bool $minified = true)
	{
		if ($minified === false && $json !== '{}') {
			$json = self::minify($json);
		}

		$result = json_decode($json, $toAssoc);

		$error = null;

		switch (json_last_error()) {
			case JSON_ERROR_DEPTH:
				$error = 'Maximum stack depth exceeded';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$error = 'Unexpected control character found';
				break;
			case JSON_ERROR_SYNTAX:
				$error = 'Syntax error, malformed JSON markup';
				break;
			case JSON_ERROR_NONE:
			case 0:
				break;
			default:
				$error = 'Unknown error in JSON: ' . json_last_error();
		}

		if (!is_null($error)) {
			throw new Exception('Invalid JSON code: ' . $error);
		}

		return $result;
	}

	public static function minify(string $json): string
	{
		$tokenizer = "/\"|(\/\*)|(\*\/)|(\/\/)|\n|\r/";
		$in_string = false;
		$in_multiline_comment = false;
		$in_singleline_comment = false;
		$tmp = null;
		$tmp2 = null;
		$new_str = [];
		$from = 0;
		$lc = null;
		$rc = null;
		$lastIndex = 0;

		while (preg_match($tokenizer, $json, $tmp, PREG_OFFSET_CAPTURE, $lastIndex)) {
			$tmp = $tmp[0];
			$lastIndex = $tmp[1] + strlen($tmp[0]);
			$lc = substr($json, 0, $lastIndex - strlen($tmp[0]));
			$rc = substr($json, $lastIndex);

			if (!$in_multiline_comment && !$in_singleline_comment) {
				$tmp2 = substr($lc, $from);
				if (!$in_string) {
					$tmp2 = preg_replace("/(\n|\r|\s)*/", null, $tmp2);
				}

				$new_str[] = $tmp2;
			}

			$from = $lastIndex;

			if ($tmp[0] == '"' && !$in_multiline_comment && !$in_singleline_comment) {
				preg_match("/(\\\\)*$/", $lc, $tmp2);

				if (!$in_string || !$tmp2 || (strlen($tmp2[0]) % 2) == 0) // start of string with ", or unescaped " character found to end string
				{
					$in_string = !$in_string;
				}

				$from--; // include " character in next catch
				$rc = substr($json, $from);
			} else if ($tmp[0] == "/*" && !$in_string && !$in_multiline_comment && !$in_singleline_comment) {
				$in_multiline_comment = true;
			} else if ($tmp[0] == "*/" && !$in_string && $in_multiline_comment && !$in_singleline_comment) {
				$in_multiline_comment = false;
			} else if ($tmp[0] == "//" && !$in_string && !$in_multiline_comment && !$in_singleline_comment) {
				$in_singleline_comment = true;
			} else if (($tmp[0] == "\n" || $tmp[0] == "\r") && !$in_string && !$in_multiline_comment && $in_singleline_comment) {
				$in_singleline_comment = false;
			} else if (!$in_multiline_comment && !$in_singleline_comment && !(preg_match("/\n|\r|\s/", $tmp[0]))) {
				$new_str[] = $tmp[0];
			}
		}
		$new_str[] = $rc;

		return implode(null, $new_str);
	}
}
/* EOF */