<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\common;

class utf8tools
{
	/**
	 * @param string $hexCode : the 4-character hexcode representation for desired UNICODE character, like '005E' for 'n'
	 *
	 * @return string : Unicode character
	 */
	public function utf8char(string $hexCode): string
	{
		return json_decode('"\u' . $hexCode . '"');
	}

	/**
	 * @param array $hexCodes : takes an array of the 4-character hexcode representations
	 *                        for desired UNICODE characters, like '005E' for 'n'
	 *
	 * @return array : array of unicode characters
	 */
	public function getUtf8charArray(array $hexCodes): array
	{
		$utf8array = [];
		foreach ($hexCodes as $hex) {
			$utf8array[] = $this->utf8char($hex);
		}

		return $utf8array;
	}

	public function getUtf8charArrayRange(string $lowerBoundary, string $upperBoundary): array
	{
		$charArray = [];
		$l = hexdec($lowerBoundary);
		$u = hexdec($upperBoundary);
		if ($l > $u) {
			// swap
			$x = $l;
			$l = $u;
			unset($x);
		}
		for ($i = $l; $i <= $u; $i++) {
			$hexcode = str_pad(dechex($i), 4, '0', STR_PAD_LEFT);
			$charArray[] = $this->utf8char($hexcode);
		}

		return $charArray;
	}
}