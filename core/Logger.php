<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use Exception;
use Throwable;

class Logger
{
	const dnl = PHP_EOL . PHP_EOL;
	private int $maxLogSize = 10000000;
	private ?string $logEmailRecipient;
	private string $logdir;

	public function __construct(EnvironmentHandler $environmentHandler, CoreProperties $coreProperties)
	{
		$this->logEmailRecipient = $environmentHandler->getLogRecipientEmail();
		$this->logdir = $coreProperties->getSiteLogsDir();
		if (!is_dir($this->logdir)) {
			throw new Exception('Log directory does not exist: ' . $this->logdir);
		}
	}

	public function log(string $message, ?Throwable $exceptionToLog = null): void
	{
		$hashableContent = $message;

		if (!is_null($exceptionToLog)) {
			if (trim($message) !== '') {
				$message .= self::dnl;
			}
			$message .= get_class($exceptionToLog) . ': (' . $exceptionToLog->getCode() . ') "' . $exceptionToLog->getMessage() . '"' . PHP_EOL;
			$message .= 'thrown in file: ' . $exceptionToLog->getFile() . ' (Line: ' . $exceptionToLog->getLine() . ')' . self::dnl;
			$hashableContent = $message;
			$message .= $exceptionToLog->getTraceAsString();

			// Don't use dynamic data ($traceLineArray['args']) from backtrace for hash-able content
			foreach ($exceptionToLog->getTrace() as $traceLineArray) {
				$hashableContent .=
					($traceLineArray['file'] ?? '') .
					($traceLineArray['line'] ?? '') .
					($traceLineArray['class'] ?? '') .
					($traceLineArray['type'] ?? '') .
					($traceLineArray['function'] ?? '') .
					PHP_EOL;
			}
		}

		$hash = hash('sha256', $hashableContent, false);
		$this->deliverMessage($hash, $message);
	}

	private function deliverMessage(string $hash, string $message): void
	{
		$ticketFile = 'ticket_' . $hash . '.txt';
		$ticketFullPath = $this->logdir . $ticketFile;
		$isNewIssue = $this->isNewIssue($ticketFullPath);
		$this->writeMessage($message, $ticketFullPath);
		if ($isNewIssue) {
			$this->mailMessage('Ticketfile: ' . $ticketFile . self::dnl . $message);
		}
	}

	private function isNewIssue(string $ticketFullPath): bool
	{
		// File will only be written, if the desired content is unique / not already present (determined by the hash):
		if (!file_exists($ticketFullPath)) {
			return true;
		}
		$modified = filemtime($ticketFullPath);
		// Older than 24h?
		if (($modified + 86400) < time()) {
			return true;
		}

		return false;
	}

	private function writeMessage(string $message, string $filenameFullPath): void
	{
		$message .= self::dnl . '$_SERVER = ' . print_r($_SERVER, true);
		$message .= self::dnl . '$_GET = ' . print_r($_GET, true);
		$message .= self::dnl . '$_POST = ' . print_r($_POST, true);
		$message .= self::dnl . '$_FILES = ' . print_r($_FILES, true);
		$message .= self::dnl . '$_COOKIE = ' . print_r($_COOKIE, true);

		$this->checkMaxFileSize($filenameFullPath);
		// Because of date('u')-PHP-bug (always 00000)
		$mtimeParts = explode(' ', microtime());
		$timestamp = date('Y-m-d H:i:s', $mtimeParts[1]) . ',' . substr($mtimeParts[0], 2);
		$msg = $timestamp . PHP_EOL . $message . PHP_EOL . str_pad('', 70, '=') . PHP_EOL;
		error_log($msg, 3, $filenameFullPath);
	}

	private function mailMessage(string $fullMessage): void
	{
		if (trim($this->logEmailRecipient) === '') {
			return;
		}
		$headers = [
			/*'Subject: An error occurred on '.$_SERVER['SERVER_NAME'],*/
			'From: error@' . $_SERVER['SERVER_NAME'],
			'Date: ' . date('r'),
			'Content-Type: text/plain; charset=UTF-8',
		];
		error_log($fullMessage, 1, $this->logEmailRecipient, implode(PHP_EOL, $headers));
	}

	/**
	 * "Log-rotates" the given file
	 *
	 * @param string $filenameFullPath : filename with full path
	 *
	 * @return bool : true, if rotated
	 */
	private function checkMaxFileSize(string $filenameFullPath): bool
	{
		if ($this->maxLogSize <= 0) {
			return false;
		}

		if (!file_exists($filenameFullPath) || filesize($filenameFullPath) < $this->maxLogSize) {
			return false;
		}

		$filePathParts = explode(DIRECTORY_SEPARATOR, $filenameFullPath);
		$fileName = array_pop($filePathParts);

		$i = 0;
		foreach (scandir(implode(DIRECTORY_SEPARATOR, $filePathParts)) as $f) {
			$pos = (strpos($f, $fileName));
			if ($pos === false) {
				continue;
			}

			$fileNum = substr($f, $pos + strlen($fileName) + 1);

			if ($fileNum > $i) {
				$i = $fileNum;
			}
		}

		++$i;

		$newFilename = $filenameFullPath . '.' . $i;

		/* rename() does not work proper */
		$fp = fopen($newFilename, 'a+');
		fwrite($fp, file_get_contents($filenameFullPath));
		fclose($fp);

		$fp = fopen($filenameFullPath, 'w+');
		fclose($fp);

		return true;
	}
}
/* EOF */ 