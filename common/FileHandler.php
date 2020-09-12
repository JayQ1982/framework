<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\common;

use framework\core\HttpResponse;

class FileHandler
{
	private string $path;
	private ?string $individualFileName;
	private int $maxAge;

	public function __construct(string $path, ?string $individualFileName = null, int $maxAge = 0)
	{
		$this->path = $path;
		$this->individualFileName = $individualFileName;
		$this->maxAge = $maxAge;
	}

	public static function getExtension(string $filename)
	{
		return substr($filename, strrpos($filename, '.') + 1);
	}

	public function output(bool $forceDownload = false): void
	{
		$httpResponse = HttpResponse::createResponseFromFilePath($this->path, $forceDownload, $this->individualFileName, $this->maxAge);
		$httpResponse->sendAndExit();
	}

	public static function removeFile(string $directory, string $token, string $filename): void
	{
		$path = $directory . $token . '.' . self::getExtension($filename);
		if (file_exists($path) && is_file($path)) {
			unlink($path);
		}
	}

	public static function getfilesize(string $filePath): string
	{
		if (!file_exists($filePath)) {
			return '0 KB';
		}

		$bytes = filesize($filePath);

		if ($bytes >= 1099511627776) {
			$return = round($bytes / 1024 / 1024 / 1024 / 1024, 2);
			$suffix = "TB";
		} else if ($bytes >= 1073741824) {
			$return = round($bytes / 1024 / 1024 / 1024, 2);
			$suffix = "GB";
		} else if ($bytes >= 1048576) {
			$return = round($bytes / 1024 / 1024, 2);
			$suffix = "MB";
		} else if ($bytes >= 1024) {
			$return = round($bytes / 1024, 2);
			$suffix = "KB";
		} else {
			$return = $bytes;
			$suffix = "Byte";
		}
		if ($return === 1) {
			$return .= " " . $suffix;
		} else {
			$return .= " " . $suffix . "s";
		}
		return $return;
	}
}
/* EOF */ 