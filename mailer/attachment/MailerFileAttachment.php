<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer\attachment;

use framework\mailer\MailerConstants;
use framework\mailer\MailerException;
use framework\mailer\MailerFunctions;

class MailerFileAttachment
{
	private string $path;
	private string $fileName;
	private string $type;

	public function __construct(
		string         $path,
		string         $fileName = '',
		private string $encoding = MailerConstants::ENCODING_BASE64,
		string         $type = '',
		private bool   $dispositionInline = false
	) {
		if (!in_array(needle: $this->encoding, haystack: MailerConstants::ENCODING_LIST)) {
			throw new MailerException(message: 'Invalid encoding "' . $this->encoding . '". See MailerConstants::ENCODING_LIST[].');
		}

		$path = trim($path);
		if ($path === '') {
			throw new MailerException(message: 'Empty path.');
		}
		if (!MailerFunctions::fileIsAccessible(path: $path)) {
			throw new MailerException(message: 'Could not access file: ' . $path);
		}
		$this->path = $path;

		$fileName = trim($fileName);
		if ($fileName === '') {
			$fileName = MailerFunctions::mb_pathinfo(path: $path, options: PATHINFO_BASENAME);
		}
		$this->fileName = $fileName;

		$type = trim($type);
		if ($type === '') {
			$type = MailerFunctions::filenameToType(fileName: $path);
		}
		$this->type = $type;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getFileName(): string
	{
		return $this->fileName;
	}

	public function getEncoding(): string
	{
		return $this->encoding;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function isDispositionInline(): bool
	{
		return $this->dispositionInline;
	}
}