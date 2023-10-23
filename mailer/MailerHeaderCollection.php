<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer;

class MailerHeaderCollection
{
	/** @var MailerHeader[] */
	private array $items = [];

	public function addItem(MailerHeader $mailerHeader): void
	{
		$this->items[] = $mailerHeader;
	}

	/**
	 * @return MailerHeader[]
	 */
	public function list(): array
	{
		return $this->items;
	}
}