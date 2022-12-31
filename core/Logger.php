<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

use Exception;
use Throwable;

class Logger
{
	private const dnl = PHP_EOL . PHP_EOL;
	private int $maxLogSize = 10000000;

	public function __construct(
		private readonly string $logEmailRecipient,
		private readonly string $logDirectory
	) {
		if (!is_dir(filename: $this->logDirectory)) {
			throw new Exception(message: 'Log directory does not exist: ' . $this->logDirectory);
		}
	}

	public function log(string $message, ?Throwable $exceptionToLog = null): void
	{
		$hashableContent = $message;

		if (!is_null(value: $exceptionToLog)) {
			$previousException = $exceptionToLog->getPrevious();
			$realException = is_null(value: $previousException) ? $exceptionToLog : $previousException;

			if (trim(string: $message) !== '') {
				$message .= Logger::dnl;
			}
			$message .= get_class(object: $realException) . ': (' . $realException->getCode() . ') "' . $realException->getMessage() . '"' . PHP_EOL;
			$message .= 'thrown in file: ' . $realException->getFile() . ' (Line: ' . $realException->getLine() . ')' . Logger::dnl;
			$hashableContent = $message;
			$message .= $realException->getTraceAsString();

			// Don't use dynamic data ($traceLineArray['args']) from backtrace for hash-able content
			foreach ($realException->getTrace() as $traceLineArray) {
				$hashableContent .=
					($traceLineArray['file'] ?? '') .
					($traceLineArray['line'] ?? '') .
					($traceLineArray['class'] ?? '') .
					($traceLineArray['type'] ?? '') .
					($traceLineArray['function'] ?? '') .
					PHP_EOL;
			}
		}

		$hash = hash(algo: 'sha256', data: $hashableContent);
		$this->deliverMessage(hash: $hash, message: $message);
	}

	private function deliverMessage(string $hash, string $message): void
	{
		$ticketFile = 'ticket_' . $hash . '.txt';
		$ticketFullPath = $this->logDirectory . $ticketFile;
		$isNewIssue = $this->isNewIssue(ticketFullPath: $ticketFullPath);
		$this->writeMessage(message: $message, filenameFullPath: $ticketFullPath);
		if ($isNewIssue) {
			$this->mailMessage(fullMessage: 'Ticketfile: ' . $ticketFile . Logger::dnl . $message);
		}
	}

	private function isNewIssue(string $ticketFullPath): bool
	{
		// File will only be written, if the desired content is unique / not already present (determined by the hash):
		if (!file_exists(filename: $ticketFullPath)) {
			return true;
		}
		$modified = filemtime(filename: $ticketFullPath);
		// Older than 24h?
		if (($modified + 86400) < time()) {
			return true;
		}

		return false;
	}

	private function writeMessage(string $message, string $filenameFullPath): void
	{
		$message .= Logger::dnl . '$_SERVER = ' . print_r(value: $_SERVER, return: true);
		$message .= Logger::dnl . '$_GET = ' . print_r(value: $_GET, return: true);
		$message .= Logger::dnl . '$_POST = ' . print_r(value: $_POST, return: true);
		$message .= Logger::dnl . '$_FILES = ' . print_r(value: $_FILES, return: true);
		$message .= Logger::dnl . '$_COOKIE = ' . print_r(value: $_COOKIE, return: true);

		$this->checkMaxFileSize(filenameFullPath: $filenameFullPath);
		// Because of date('u')-PHP-bug (always 00000)
		$mtimeParts = explode(separator: ' ', string: (string)microtime());
		$timestamp = date(format: 'Y-m-d H:i:s', timestamp: $mtimeParts[1]) . ',' . substr(string: $mtimeParts[0], offset: 2);
		error_log(
			message: $timestamp . PHP_EOL . $message . PHP_EOL . str_pad('', 70, '=') . PHP_EOL,
			message_type: 3,
			destination: $filenameFullPath
		);
	}

	private function mailMessage(string $fullMessage): void
	{
		if ($this->logEmailRecipient === '') {
			return;
		}
		error_log(
			message: $fullMessage,
			message_type: 1,
			destination: $this->logEmailRecipient,
			additional_headers: implode(separator: PHP_EOL, array: [
				'From: error@' . $_SERVER['SERVER_NAME'],
				'Date: ' . date(format: 'r'),
				'Content-Type: text/plain; charset=UTF-8',
			])
		);
	}

	private function checkMaxFileSize(string $filenameFullPath): void
	{
		if ($this->maxLogSize <= 0) {
			return;
		}

		if (!file_exists(filename: $filenameFullPath) || filesize(filename: $filenameFullPath) < $this->maxLogSize) {
			return;
		}

		$filePathParts = explode(separator: DIRECTORY_SEPARATOR, string: $filenameFullPath);
		$fileName = array_pop(array: $filePathParts);

		$i = 0;
		foreach (scandir(directory: implode(separator: DIRECTORY_SEPARATOR, array: $filePathParts)) as $f) {
			$pos = strpos(haystack: $f, needle: $fileName);
			if ($pos === false) {
				continue;
			}

			$fileNum = substr(string: $f, offset: $pos + strlen($fileName) + 1);

			if ($fileNum > $i) {
				$i = $fileNum;
			}
		}

		++$i;
		$newFilename = $filenameFullPath . '.' . $i;
		/* rename() does not work proper */
		$fp = fopen(filename: $newFilename, mode: 'a+');
		fwrite(stream: $fp, data: file_get_contents(filename: $filenameFullPath));
		fclose(stream: $fp);

		$fp = fopen(filename: $filenameFullPath, mode: 'w+');
		fclose(stream: $fp);
	}
}