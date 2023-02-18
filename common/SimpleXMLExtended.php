<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

use SimpleXMLElement;
use stdClass;
use Throwable;

class SimpleXMLExtended extends SimpleXMLElement
{
	public function addCData($cdata_text): void
	{
		$node = dom_import_simplexml($this);
		$no = $node->ownerDocument;
		$node->appendChild($no->createCDATASection((string)$cdata_text));
	}

	public function remove(SimpleXMLElement $node): void
	{
		$dom = dom_import_simplexml($node);
		if (isset($dom->parentNode)) {
			$dom->parentNode->removeChild($dom);
		}
	}

	public function addChild(string $qualifiedName, ?string $value = null, ?string $namespace = null): ?static
	{
		$new_child = parent::addChild($qualifiedName, null, $namespace);

		if ($new_child !== null && $value !== null) {
			$node = dom_import_simplexml($new_child);
			$no = $node->ownerDocument;
			$node->appendChild($no->createCDATASection($value));
		}

		return $new_child;
	}

	public function addArray(array|stdClass $array, SimpleXMLElement $xml = null, bool $include_null = true): bool
	{
		if ($xml === null) {
			$xml = $this;
		}

		if (is_object($array)) {
			$array = get_object_vars($array);
		}

		if (!is_array($array)) {
			return false;
		}

		foreach ($array as $key => $val) {
			if (!$include_null && $val === null) {
				continue;
			}

			if (is_object($val)) {
				$val = get_object_vars($val);
			}

			if (is_numeric($key)) {
				$key = 'item' . $key;
			}

			$child = $xml->addChild($key);
			if (is_array($val)) {
				$this->addArray($val, $child);
				continue;
			}
			if ($child instanceof SimpleXMLExtended) {
				$child->addCData($val);
			}
		}

		return true;
	}

	/**
	 * Append another xml to current xmlElement.
	 * Inspired by http://stackoverflow.com/questions/3418019/simplexml-append-one-tree-to-another
	 *
	 * @param SimpleXMLElement $xmlToAppend
	 *
	 * @return bool
	 */
	public function addXML(SimpleXMLElement $xmlToAppend): bool
	{
		$parent = dom_import_simplexml($this);
		$child = dom_import_simplexml($xmlToAppend);

		$child = $parent->ownerDocument->importNode($child, true);
		$parent->appendChild($child);

		return true;
	}

	/**
	 * Converts XML to a nested array for standardized output
	 *
	 * @param string $xml : XML as string, which will be converted
	 *
	 * @return bool|array : (nested) array on success, "false" otherwise
	 */
	public static function convertXmlToArray(string $xml): false|array
	{
		try {
			$tmp = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		} catch (Throwable) {
			return false;
		}
		if ($tmp === false) {
			return false;
		}
		// actual conversion
		$arr = JsonUtils::decodeJsonString(jsonString: JsonUtils::convertToJsonString((array)$tmp), returnAssociativeArray: true);
		SimpleXMLExtended::stringifyEmptyInnerArrays($arr);

		return $arr;
	}

	/**
	 * Sets an empty array recursively within an nested array as empty string
	 *
	 * @param     $data            : The array to clean
	 * @param int $instanceCounter : DO NOT USE THAT PARAMETER! Ignore it. It is used for inner recursion only.
	 */
	private static function stringifyEmptyInnerArrays(&$data, int $instanceCounter = 1)
	{
		if (is_array($data)) {
			if (count($data) == 0 && $instanceCounter != 1) {
				$data = '';
			} else {
				foreach ($data as &$value) {
					SimpleXMLExtended::stringifyEmptyInnerArrays($value, $instanceCounter++);
				}
			}
		}
	}
}