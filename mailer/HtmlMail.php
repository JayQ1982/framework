<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer;

class HtmlMail extends AbstractMail
{
	public function __construct(
		string $senderEmail,
		string $fromEmail,
		string $fromName,
		string $toEmail,
		string $toName,
		string $subject,
		string $htmlBody,
		string $alternativeBody,
		string $charSet = MailerConstants::CHARSET_UTF8,
		string $encoding = MailerConstants::ENCODING_QUOTED_PRINTABLE,
		int    $priority = MailerConstants::PRIORITY_NORMAL
	) {
		parent::__construct(
			senderEmail: $senderEmail,
			fromEmail: $fromEmail,
			fromName: $fromName,
			toEmail: $toEmail,
			toName: $toName,
			subject: $subject,
			charSet: $charSet,
			encoding: $encoding,
			priority: $priority
		);
		$this->setHtmlBody(htmlBody: $htmlBody, alternativeBody: $alternativeBody);
	}
}