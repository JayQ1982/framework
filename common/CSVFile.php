<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\common;

class CSVFile
{
	private string $path;
	/** @var resource|false */
	private $fileResource;
	private bool $utf8Encode;
	private array $cols = [];
	private array $data = [];

	public function __construct(string $fileName, bool $utf8Encode)
	{
		$this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
		$this->utf8Encode = $utf8Encode;
		$this->fileResource = fopen(filename: $this->path, mode: 'w');
		$this->addBOM();
	}

	private function addBOM(): void
	{
		if (!$this->utf8Encode) {
			return;
		}
		fputs(stream: $this->fileResource, data: (chr(codepoint: 0xEF) . chr(codepoint: 0xBB) . chr(codepoint: 0xBF)));
	}

	/**
	 * @param $rowNumber : string or integer
	 * @param $colName   : string or integer
	 * @param $content   : any data (scalar)
	 */
	public function addField($rowNumber, $colName, $content): void
	{
		if (!isset($this->cols[$colName])) {
			$this->cols[$colName] = $colName;
		}
		$this->data[$rowNumber][$colName] = $content;
	}

	public function load(string $delimiter = ';', string $enclosure = '"'): string
	{
		fputcsv(stream: $this->fileResource, fields: $this->cols, separator: $delimiter, enclosure: $enclosure);
		foreach ($this->data as $row) {
			fputcsv(stream: $this->fileResource, fields: $row, separator: $delimiter, enclosure: $enclosure);
		}
		fclose(stream: $this->fileResource);

		return $this->path;
	}

	public function stringToArray(string $string, string $delimiter = ';', string $enclosure = '"', string $escape = '\\', string $terminator = "\n"): array
	{
		$r = [];
		$string = trim($string);
		if ($string === '') {
			return $r;
		}
		$rows = explode(separator: $terminator, string: $string);
		$names = array_shift(array: $rows);
		$r[] = str_getcsv(string: $names, separator: $delimiter, enclosure: $enclosure, escape: $escape);
		foreach ($rows as $row) {
			$row = trim($row);
			if ($row !== '') {
				$r[] = str_getcsv(string: $row, separator: $delimiter, enclosure: $enclosure, escape: $escape);
			}
		}

		return $r;
	}
}