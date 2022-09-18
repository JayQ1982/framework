<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

use framework\core\HttpResponse;

class FileHandler
{
	public function __construct(
		private readonly string  $path,
		private readonly ?string $individualFileName = null,
		private readonly int     $maxAge = 0
	) {
	}

	public static function getExtension(string $filename): false|string
	{
		return substr(string: $filename, offset: strrpos(haystack: $filename, needle: '.') + 1);
	}

	public function output(bool $forceDownload = false): void
	{
		HttpResponse::createResponseFromFilePath(
			absolutePathToFile: $this->path,
			forceDownload: $forceDownload,
			individualFileName: $this->individualFileName,
			maxAge: $this->maxAge
		)->sendAndExit();
	}

	public static function removeFile(string $directory, string $token, string $filename): void
	{
		$path = $directory . $token . '.' . FileHandler::getExtension(filename: $filename);
		if (file_exists(filename: $path) && is_file(filename: $path)) {
			unlink(filename: $path);
		}
	}

	public static function renderFileSize(string $filePath): string
	{
		if (!file_exists(filename: $filePath)) {
			return '0 KB';
		}

		return StringUtils::formatBytes(bytes: filesize(filename: $filePath));
	}
}