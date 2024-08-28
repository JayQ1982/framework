<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

use framework\Core;
use Throwable;

class LogFile
{
	/** @var LogFile[] */
	private static array $openLogFiles = [];
	/** @var resource */
	private $stream;

	public function __construct(string $group, string $logFileName)
	{
		$groupDirectoryPath = LogFile::createDirectoryIfMissing(path: Core::get()->logDirectory . $group);
		$dateArr = explode(separator: '-', string: date(format: 'Y-m-d'));
		$yearDirectoryPath = LogFile::createDirectoryIfMissing(path: $groupDirectoryPath . DIRECTORY_SEPARATOR . $dateArr[0]);
		$monthDirectoryPath = LogFile::createDirectoryIfMissing(path: $yearDirectoryPath . DIRECTORY_SEPARATOR . $dateArr[1]);
		$dayDirectoryPath = LogFile::createDirectoryIfMissing(path: $monthDirectoryPath . DIRECTORY_SEPARATOR . $dateArr[2]);
		$this->stream = fopen(
			filename: $dayDirectoryPath . DIRECTORY_SEPARATOR . $logFileName . '-' . uniqid(more_entropy: true) . '.log',
			mode: 'a'
		);
		LogFile::$openLogFiles[$group . '-' . $logFileName] = $this;
	}

	public function write(string $line): void
	{
		if (!is_resource(value: $this->stream)) {
			return;
		}
		$mtimeParts = explode(separator: ' ', string: (string)microtime());
		$timestamp = date(format: 'Y-m-d H:i:s', timestamp: $mtimeParts[1]) . ',' . substr(string: $mtimeParts[0], offset: 2);
		fwrite(stream: $this->stream, data: $timestamp . ' - ' . $line . PHP_EOL);
	}

	public static function info(string $logFileName, string $message): void
	{
		LogFile::log(group: 'info', logFileName: $logFileName, message: $message);
	}

	public static function debug(string $logFileName, string $message): void
	{
		LogFile::log(group: 'debug', logFileName: $logFileName, message: $message);
	}

	public static function error(string $logFileName, string $message): void
	{
		LogFile::log(group: 'error', logFileName: $logFileName, message: $message);
	}

	private static function log(string $group, string $logFileName, string $message): void
	{
		if (array_key_exists(key: $group . '-' . $logFileName, array: LogFile::$openLogFiles)) {
			$logFile = LogFile::$openLogFiles[$group . '-' . $logFileName];
		} else {
			$logFile = new LogFile(group: $group, logFileName: $logFileName);
		}
		$logFile->write(line: $message);
	}

	private static function createDirectoryIfMissing($path): string
	{
		if (!is_dir(filename: $path)) {
			try {
				mkdir(directory: $path);
			} catch (Throwable $throwable) {
				if (str_contains(haystack: $throwable->getMessage(), needle: 'mkdir(): Die Datei existiert bereits')) {
					return $path;
				}
				throw $throwable;
			}
		}

		return $path;
	}

	public function __destruct()
	{
		if (is_resource(value: $this->stream)) {
			fclose(stream: $this->stream);
		}
	}
}