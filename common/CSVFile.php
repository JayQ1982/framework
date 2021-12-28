<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\common;

use framework\core\HttpResponse;

class CSVFile
{
	private string $fileName;
	private array $headersList;
	private bool $utf8Encode;
	private string $delimiter;
	private string $enclosure;
	private array $rows = [];

	public function __construct(
		string $fileName,
		array  $headersList = [],
		bool   $utf8Encode = true,
		string $delimiter = ';',
		string $enclosure = '"'
	) {
		$this->fileName = $fileName;
		$this->headersList = $headersList;
		$this->utf8Encode = $utf8Encode;
		$this->delimiter = $delimiter;
		$this->enclosure = $enclosure;
	}

	public function addRow(array $data): void
	{
		$this->rows[] = $data;
	}

	public static function stringToArray(string $string, string $delimiter = ';', string $enclosure = '"', string $escape = '\\', string $terminator = "\n"): array
	{
		$r = [];
		$string = trim(string: $string);
		if ($string === '') {
			return $r;
		}
		$rows = explode(separator: $terminator, string: $string);
		$names = array_shift(array: $rows);
		$r[] = str_getcsv(string: $names, separator: $delimiter, enclosure: $enclosure, escape: $escape);
		foreach ($rows as $row) {
			$row = trim(string: $row);
			if ($row !== '') {
				$r[] = str_getcsv(string: $row, separator: $delimiter, enclosure: $enclosure, escape: $escape);
			}
		}

		return $r;
	}

	public function pushDownloadAndExit(): void
	{
		$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . date(format: 'YmdHis') . rand(min: 10000, max: 99999) . '.csv';
		$fileResource = fopen(filename: $path, mode: 'w');
		if ($this->utf8Encode) {
			fputs(stream: $fileResource, data: (chr(codepoint: 0xEF) . chr(codepoint: 0xBB) . chr(codepoint: 0xBF))); // Byte Order Mark (BOM)
		}

		if (count($this->headersList) > 0) {
			fputcsv(stream: $fileResource, fields: $this->headersList, separator: $this->delimiter, enclosure: $this->enclosure);
		}
		foreach ($this->rows as $row) {
			fputcsv(stream: $fileResource, fields: $row, separator: $this->delimiter, enclosure: $this->enclosure);
		}
		fclose(stream: $fileResource);

		$httpResponse = HttpResponse::createResponseFromFilePath(
			absolutePathToFile: $path,
			forceDownload: true,
			individualFileName: $this->fileName,
			maxAge: 0
		);
		$httpResponse->sendAndExit();
	}
}