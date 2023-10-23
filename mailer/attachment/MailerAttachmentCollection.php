<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer\attachment;

use framework\mailer\MailerException;

class MailerAttachmentCollection
{
	/** @var MailerFileAttachment[]|MailerStringAttachment[] */
	private array $items = [];

	public function addItem(MailerFileAttachment|MailerStringAttachment $mailerAttachment): void
	{
		$fileName = $mailerAttachment->fileName;
		if (array_key_exists(key: $fileName, array: $this->items)) {
			throw new MailerException(message: 'Attachment with fileName "' . $fileName . '" already exists.');
		}
		$this->items[$fileName] = $mailerAttachment;
	}

	/**
	 * @return MailerFileAttachment[]|MailerStringAttachment[]
	 */
	public function list(): array
	{
		return $this->items;
	}

	public function hasInlineImages(): bool
	{
		foreach ($this->items as $mailerAttachment) {
			if ($mailerAttachment->dispositionInline) {
				return true;
			}
		}

		return false;
	}

	public function hasAttachments(): bool
	{
		foreach ($this->items as $mailerAttachment) {
			if (!$mailerAttachment->dispositionInline) {
				return true;
			}
		}

		return false;
	}
}