<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer;

use Exception;
use framework\mailer\attachment\MailerFileAttachment;
use framework\mailer\attachment\MailerStringAttachment;
use framework\vendor\PHPMailer\PHPMailer;
use framework\vendor\PHPMailer\SMTP;
use LogicException;

class ExtendedPHPMailer extends PHPMailer
{
	private bool $isSent = false;

	public function __construct(string $fromEmail, ?string $fromName = null, ?string $replyToEmail = null, ?string $replyToName = null, string $charset = PHPMailer::CHARSET_UTF8)
	{
		parent::__construct(true);

		if (is_null($fromName)) {
			$fromName = $fromEmail;
		}

		$this->setFrom($fromEmail, $fromName, true);

		if (is_null($replyToEmail)) {
			$replyToEmail = $fromEmail;
		}

		if (is_null($replyToName)) {
			$replyToName = $fromName;
		}

		$this->addReplyTo($replyToEmail, $replyToName);
		$this->CharSet = $charset;
		$this->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;
		$this->Priority = 3;
	}

	/**
	 * @param string                                          $toEmail
	 * @param string|null                                     $toName
	 * @param string                                          $subject
	 * @param string                                          $body
	 * @param bool                                            $isHTML
	 * @param string                                          $altBody
	 * @param MailerFileAttachment[]|MailerStringAttachment[] $attachments
	 * @param array                                           $cc
	 * @param array                                           $bcc
	 */
	public function sendMail(string $toEmail, ?string $toName, string $subject, string $body, bool $isHTML = false, string $altBody = '', array $attachments = [], array $cc = [], array $bcc = []): void
	{
		if ($this->isSent) {
			throw new LogicException('You cannot send the same email multiple times. You need to create a new instance of this class for another emails instead.');
		}

		$this->addAddress($toEmail, $toName);
		$this->Subject = $subject;
		$this->Body = $body;

		if ($isHTML) {
			$this->isHTML(true);
			if ($altBody !== '') {
				$this->AltBody = $altBody;
			}
		}

		if (count($attachments) > 0) {
			foreach ($attachments as $attachment) {
				$disposition = $attachment->isDispositionInline() ? 'inline' : 'attachment';
				if ($attachment instanceof MailerFileAttachment) {
					$this->addAttachment($attachment->getPath(), $attachment->getFileName(), $attachment->getEncoding(), $attachment->getType(), $disposition);
				} else if ($attachment instanceof MailerStringAttachment) {
					$this->addStringAttachment($attachment->getContentString(), $attachment->getFileName(), $attachment->getEncoding(), $attachment->getType(), $disposition);
				} else {
					throw new LogicException('Attachments must be an instance of MailerFileAttachment or MailerStringAttachment');
				}
			}
		}

		foreach ($cc as $emailAddress => $name) {
			$this->addCC($emailAddress, $name);
		}

		foreach ($bcc as $emailAddress => $name) {
			$this->addBCC($emailAddress, $name);
		}

		if (!$this->send()) {
			throw new Exception($this->ErrorInfo);
		}
		$this->isSent = true;
	}

	public function setSender(string $Sender): void
	{
		$this->Sender = $Sender;
	}

	public function useSMTP(string $host, int $port, string $username, string $password, int $debugLevel = SMTP::DEBUG_OFF, string $secure = ''): void
	{
		$this->Mailer = 'smtp';
		$this->Host = $host;
		$this->Port = $port;
		$this->Username = $username;
		$this->Password = $password;
		$this->SMTPAuth = true;
		$this->SMTPDebug = $debugLevel;
		$this->SMTPSecure = $secure;
	}

	public static function validateAddress($address, $patternselect = null): bool
	{
		//Reject line breaks in addresses; it's valid RFC5322, but not RFC5321
		if (str_contains($address, "\n") || str_contains($address, "\r")) {
			return false;
		}

		return (bool)filter_var($address, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE);
	}
}