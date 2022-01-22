<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

use Exception;
use stdClass;

class JsonUtils
{
	public static function decodeJsonString($jsonString, bool $returnAssociativeArray): stdClass|array
	{
		return json_decode(json: $jsonString, associative: $returnAssociativeArray, flags: JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
	}

	public static function convertToJsonString(mixed $valueToConvert): string
	{
		return json_encode(value: $valueToConvert, flags: JSON_UNESCAPED_UNICODE | JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
	}

	public static function decodeFile($filePath, $isMinified, $returnAssociativeArray = false): stdClass|array
	{
		if (file_exists($filePath) === false) {
			throw new Exception('JSON-File does not exist: ' . $filePath);
		}
		$jsonString = file_get_contents($filePath);

		if ($isMinified === false && $jsonString !== '{}') {
			$jsonString = JsonUtils::minify(jsonString: $jsonString);
		}

		return JsonUtils::decodeJsonString(jsonString: $jsonString, returnAssociativeArray: $returnAssociativeArray);
	}

	public static function minify(string $jsonString): string
	{
		$tokenizer = "/\"|(\/\*)|(\*\/)|(\/\/)|\n|\r/";
		$in_string = false;
		$in_multiline_comment = false;
		$in_singleline_comment = false;
		$tmp = null;
		$tmp2 = null;
		$new_str = [];
		$from = 0;
		$rc = null;
		$lastIndex = 0;

		while (preg_match($tokenizer, $jsonString, $tmp, PREG_OFFSET_CAPTURE, $lastIndex)) {
			$tmp = $tmp[0];
			$lastIndex = $tmp[1] + strlen($tmp[0]);
			$lc = substr($jsonString, 0, $lastIndex - strlen($tmp[0]));
			$rc = substr($jsonString, $lastIndex);

			if (!$in_multiline_comment && !$in_singleline_comment) {
				$tmp2 = substr($lc, $from);
				if (!$in_string) {
					$tmp2 = preg_replace(
						pattern: "/(\n|\r|\s)*/",
						replacement: '',
						subject: $tmp2
					);
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
				$rc = substr($jsonString, $from);
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

		return implode('', $new_str);
	}
}