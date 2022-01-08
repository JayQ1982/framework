<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer\attachment;

use framework\vendor\PHPMailer\PHPMailer;

class MailerStringAttachment
{
	private string $contentString;
	private string $fileName;
	private string $encoding;
	private string $type;
	private bool $dispositionInline;

	public function __construct(string $contentString, string $forceFileName = '', string $encoding = PHPMailer::ENCODING_BASE64, string $type = '', bool $dispositionInline = false)
	{
		$this->contentString = $contentString;
		$this->fileName = $forceFileName;
		$this->encoding = $encoding;
		$this->type = $type;
		$this->dispositionInline = $dispositionInline;
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