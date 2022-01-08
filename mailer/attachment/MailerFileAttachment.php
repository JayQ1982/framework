<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer\attachment;

use framework\vendor\PHPMailer\PHPMailer;

class MailerFileAttachment
{
	private string $path;
	private string $fileName;
	private string $encoding;
	private string $type;
	private bool $dispositionInline;

	public function __construct(string $path, string $forceFileName = '', string $encoding = PHPMailer::ENCODING_BASE64, string $type = '', bool $dispositionInline = false)
	{
		$this->path = $path;
		$this->fileName = $forceFileName;
		$this->encoding = $encoding;
		$this->type = $type;
		$this->dispositionInline = $dispositionInline;
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