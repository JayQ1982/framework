<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer\attachment;

use framework\mailer\MailerConstants;
use framework\mailer\MailerException;
use framework\mailer\MailerFunctions;

class MailerStringAttachment
{
	private string $contentString;
	private string $fileName;
	private string $type;
	private string $encoding = MailerConstants::ENCODING_BASE64;

	public function __construct(
		string       $contentString,
		string       $fileName,
		string       $type,
		private bool $dispositionInline = false
	) {
		$contentString = trim($contentString);
		$fileName = trim($fileName);

		if ($contentString === '' || $fileName === '') {
			throw new MailerException(message: 'Empty contentString or fileName.');
		}
		$type = trim($type);
		if ($type === '') {
			$type = MailerFunctions::filenameToType(fileName: $fileName);
		}

		$this->contentString = $contentString;
		$this->fileName = $fileName;
		$this->type = $type;
	}

	public function getContentString(): string
	{
		return $this->contentString;
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