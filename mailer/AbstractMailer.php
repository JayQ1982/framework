<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer;

abstract class AbstractMailer
{
	abstract public function headerHasTo(): bool;

	abstract public function headerHasSubject(): bool;

	abstract public function getMaxLineLength(): int;

	abstract public function sendMail(
		AbstractMail   $abstractMail,
		MailMimeHeader $mailMimeHeader,
		MailMimeBody   $mailMimeBody
	): void;

	public function getServerName(): string
	{
		return gethostbyaddr(ip: $_SERVER['SERVER_ADDR']);
	}
}