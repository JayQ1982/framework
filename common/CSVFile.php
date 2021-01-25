<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\common;

class CSVFile
{
	private string $path;
	private string $fileName;
	/** @var resource|false */
	private $fileResource;
	private bool $utf8Encode;
	private array $cols = [];
	private array $data = [];

	public function __construct(string $fileName, bool $utf8Encode)
	{
		$this->fileName = $fileName;
		$this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
		$this->utf8Encode = $utf8Encode;
		$this->fileResource = fopen($this->path, 'w');
		$this->addBOM();
	}

	private function addBOM(): void
	{
		if (!$this->utf8Encode) {
			return;
		}
		fputs($this->fileResource, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
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
		fputcsv($this->fileResource, $this->cols, $delimiter, $enclosure);
		foreach ($this->data as $row) {
			fputcsv($this->fileResource, $row, $delimiter, $enclosure);
		}
		fclose($this->fileResource);

		return $this->path;
	}

	public function stringToArray(string $string, string $delimiter = ';', string $enclosure = '"', string $escape = '\\', string $terminator = "\n"): array
	{
		$r = [];
		if (trim($string) == '') {
			return $r;
		}
		$rows = explode($terminator, trim($string));
		$names = array_shift($rows);
		if (!function_exists('str_getcsv')) {
			$r[] = $this->my_str_getcsv($names, $delimiter, $enclosure);
		} else {
			$r[] = str_getcsv($names, $delimiter, $enclosure, $escape);
		}
		$nc = count($r);
		foreach ($rows as $row) {
			if (trim($row)) {
				if (!function_exists('str_getcsv')) {
					$values = $this->my_str_getcsv($row, $delimiter, $enclosure);
				} else {
					$values = str_getcsv($row, $delimiter, $enclosure, $escape);
				}
				if (!$values) {
					$values = array_fill(0, $nc, null);
				}
				$r[] = $values;
			}
		}

		return $r;
	}

	/**
	 * @param        $input
	 * @param string $delimiter
	 * @param string $enclosure
	 *
	 * @return array|false|null
	 */
	private function my_str_getcsv($input, string $delimiter = ',', string $enclosure = '"'): bool|array|null
	{
		$temp = fopen("php://memory", "rw");
		fwrite($temp, $input);
		fseek($temp, 0);
		if ($enclosure == '') {
			$enclosure = '"';
		}
		$r = fgetcsv($temp, 4096, $delimiter[0], $enclosure[0]);
		fclose($temp);

		return $r;
	}
}