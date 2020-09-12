<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\mailer;

use Throwable;
use Exception;

/**
 * PHPMailer wrapper without any dependencies to the framework
 */
abstract class Mailer
{
	private string $fromEmail;
	private ?string $fromName;
	private ?string $replyToEmail;
	private ?string $replyToName;
	private string $charset;
	private string $encoding = 'quoted-printable';
	private int $priority = 3;
	private bool $readingConfirmation = false;
	private array $customHeaders = [];
	private bool $smtp = false;
	private ?string $smtp_host = null;
	private ?int $smtp_port = null;
	private ?string $smtp_username = null;
	private ?string $smtp_password = null;
	private ?int $smtp_debugLevel = null; // See PHPMailer->SMTPDebug for available options
	private ?string $smtp_secure = null; // See PHPMailer->SMTPSecure for available options
	private ?Throwable $lastException = null;

	protected function __construct(string $fromEmail, ?string $fromName, ?string $replyToEmail, ?string $replyToName, string $charset = 'UTF-8')
	{
		$this->setFromEmail($fromEmail);
		$this->setFromName($fromName);
		$this->setReplyToEmail($replyToEmail);
		$this->setReplyToName($replyToName);
		$this->setCharset($charset);
	}

	public function setFromEmail(string $fromEmail): void
	{
		$this->fromEmail = $fromEmail;
	}

	/**
	 * @param string|null $fromName : null = use fromEmail instead
	 */
	public function setFromName(?string $fromName): void
	{
		$this->fromName = ($fromName !== null && $fromName !== '') ? $fromName : $this->fromEmail;
	}

	/**
	 * @param string|null $replyToEmail : null = use fromEmail instead
	 */
	public function setReplyToEmail(?string $replyToEmail): void
	{
		$this->replyToEmail = ($replyToEmail !== null && $replyToEmail !== '') ? $replyToEmail : $this->fromEmail;
	}

	/**
	 * @param string|null $replyToName : null = use fromName instead
	 */
	public function setReplyToName(?string $replyToName): void
	{
		$this->replyToName = ($replyToName !== null && $replyToName !== '') ? $replyToName : $this->fromName;
	}

	public function setCharset(string $charset): void
	{
		$this->charset = $charset;
	}

	public function setEncoding(string $encoding): void
	{
		$this->encoding = $encoding;
	}

	public function setPriority(int $priority): void
	{
		$this->priority = $priority;
	}

	public function setReadingConfirmation(bool $readingConfirmation): void
	{
		$this->readingConfirmation = $readingConfirmation;
	}

	public function setCustomHeaders(array $customHeaders): void
	{
		$this->customHeaders = $customHeaders;
	}

	public function useSMTP(string $host, int $port, string $username, string $password, int $debugLevel = 0, string $secure = ''): void
	{
		$this->smtp = true;
		$this->smtp_host = $host;
		$this->smtp_port = $port;
		$this->smtp_username = $username;
		$this->smtp_password = $password;
		$this->smtp_debugLevel = $debugLevel;
		$this->smtp_secure = $secure;
	}

	public function sendMail(string $toEmail, ?string $toName, string $subject, string $body, string $format = 'text', string $altBody = '', array $attArr = [], array $cc = [], array $bcc = []): bool
	{
		$PHPMailer = new ExtendedPHPMailer(true);
		$PHPMailer->From = $this->fromEmail;
		$PHPMailer->FromName = $this->fromName;
		$PHPMailer->addReplyTo($this->replyToEmail, $this->replyToName);
		$PHPMailer->Sender = $this->fromEmail;
		$PHPMailer->CharSet = $this->charset;
		$PHPMailer->Encoding = $this->encoding;
		$PHPMailer->Priority = $this->priority;

		if ($this->readingConfirmation) {
			$PHPMailer->ConfirmReadingTo = $this->fromEmail;
		}

		if (count($this->customHeaders) > 0) {
			foreach ($this->customHeaders as $customHeader) {
				$PHPMailer->addCustomHeader($customHeader);
			}
		}

		if ($this->smtp) {
			$PHPMailer->Mailer = 'smtp';
			$PHPMailer->Host = $this->smtp_host;
			$PHPMailer->Port = $this->smtp_port;
			$PHPMailer->Username = $this->smtp_username;
			$PHPMailer->Password = $this->smtp_password;
			$PHPMailer->SMTPAuth = true;
			$PHPMailer->SMTPDebug = $this->smtp_debugLevel;
			$PHPMailer->SMTPSecure = $this->smtp_secure;
		}

		$PHPMailer->addAddress($toEmail, $toName);
		$PHPMailer->Subject = $subject;
		$PHPMailer->Body = $body;

		if ($format == 'html') {
			$PHPMailer->isHTML(true);
			if ($altBody != '') {
				$PHPMailer->AltBody = $altBody;
			}
		}

		if (count($attArr) != 0) {
			foreach ($attArr as $attachment) {
				if (isset($attachment['name']) && $attachment['name'] != '' && isset($attachment['path']) && file_exists($attachment['path'])) {
					$PHPMailer->addAttachment($attachment['path'], $attachment['name']);
				} else {
					$PHPMailer->addStringAttachment($attachment['string'], $attachment['filename'], $attachment['encoding'], $attachment['type']);
				}
			}
		}

		foreach ($cc as $key => $val) {
			$PHPMailer->addCC($key, $val);
		}

		foreach ($bcc as $key => $val) {
			$PHPMailer->addBCC($key, $val);
		}

		try {
			if (!$PHPMailer->send()) {
				throw new Exception($PHPMailer->ErrorInfo);
			}
		} catch (Throwable $t) {
			$this->lastException = $t;

			return false;
		}

		return true;
	}

	public function getLastException(): ?Throwable
	{
		return $this->lastException;
	}
}
/* EOF */