<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\core;

use framework\mailer\Mailer;

class FrameworkMailer extends Mailer
{
	private Logger $logger;

	public function __construct(Logger $logger, EnvironmentHandler $environmentHandler)
	{
		$this->logger = $logger;

		$fromEmail = $environmentHandler->getDefaultFromEmail();
		$fromName = $environmentHandler->getDefaultFromName();
		$replyToEmail = $environmentHandler->getDefaultReplyToEmail();
		$replyToName = $environmentHandler->getDefaultReplyToName();

		parent::__construct($fromEmail, $fromName, $replyToEmail, $replyToName);
	}

	public function sendMail(string $toEmail, ?string $toName, string $subject, string $body, string $format = 'text', string $altBody = '', array $attArr = [], array $cc = [], array $bcc = []): bool
	{
		$sendResult = parent::sendMail($toEmail, $toName, $subject, $body, $format, $altBody, $attArr, $cc, $bcc);

		if ($sendResult === false) {
			$this->logger->log($this->getLastException()->getMessage(), $this->getLastException());
		}

		return $sendResult;
	}
}
/* EOF */