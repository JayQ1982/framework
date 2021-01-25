<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
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

	public static function getExtension(string $filename): false|string
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
		$path = $directory . $token . '.' . FileHandler::getExtension($filename);
		if (file_exists($path) && is_file($path)) {
			unlink($path);
		}
	}

	public static function renderFileSize(string $filePath): string
	{
		if (!file_exists($filePath)) {
			return '0 KB';
		}

		return StringUtils::formatBytes(filesize($filePath));
	}
}