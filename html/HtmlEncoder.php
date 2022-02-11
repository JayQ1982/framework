<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\html;

use stdClass;

class HtmlEncoder
{
	public static function encode(null|string|float|int|bool $value): string
	{
		return is_null(value: $value) ? '' : htmlspecialchars(string: $value, flags: ENT_QUOTES);
	}

	public static function encodeKeepQuotes(null|string|float|int|bool $value): string
	{
		return is_null(value: $value) ? '' : htmlspecialchars(string: $value, flags: ENT_NOQUOTES);
	}

	public static function encodeArray(array $array, bool $keepQuotes): array
	{
		foreach ($array as $key => $val) {
			if (is_array(value: $val)) {
				$array[$key] = HtmlEncoder::encodeArray(array: $val, keepQuotes: $keepQuotes);
				continue;
			}
			if (is_object(value: $val)) {
				$array[$key] = HtmlEncoder::encodeObject(object: $val, keepQuotes: $keepQuotes);
				continue;
			}
			if ($keepQuotes) {
				$array[$key] = HtmlEncoder::encodeKeepQuotes(value: $val);
				continue;
			}
			$array[$key] = HtmlEncoder::encode(value: $val);
		}

		return $array;
	}

	public static function encodeObject(stdClass $object, bool $keepQuotes): stdClass
	{
		foreach (get_object_vars(object: $object) as $key => $val) {
			if (is_array(value: $val)) {
				$object->{$key} = HtmlEncoder::encodeArray(array: $val, keepQuotes: $keepQuotes);
				continue;
			}
			if (is_object(value: $val)) {
				$object->{$key} = HtmlEncoder::encodeObject(object: $val, keepQuotes: $keepQuotes);
				continue;
			}
			if ($keepQuotes) {
				$object->{$key} = HtmlEncoder::encodeKeepQuotes(value: $val);
				continue;
			}
			$object->{$key} = HtmlEncoder::encode(value: $val);
		}

		return $object;
	}
}