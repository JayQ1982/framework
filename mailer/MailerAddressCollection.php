<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer;

class MailerAddressCollection
{
	/** @var MailerAddress[] */
	private array $items = [];

	public function addItem(MailerAddress $mailerAddress): void
	{
		$email = $mailerAddress->getPunyEncodedEmail();
		if (array_key_exists(key: $email, array: $this->items)) {
			throw new MailerException(message: 'Address exists already: ' . $email);
		}

		$this->items[$email] = $mailerAddress;
	}

	/**
	 * @return MailerAddress[]
	 */
	public function list(MailerAddressKindEnum $mailerAddressKindEnum): array
	{
		$list = [];
		foreach ($this->items as $mailerAddress) {
			if ($mailerAddress->mailerAddressKindEnum !== $mailerAddressKindEnum) {
				continue;
			}
			$list[] = $mailerAddress;
		}

		return $list;
	}

	public function has(MailerAddressKindEnum $mailerAddressKindEnum): bool
	{
		return (count(value: $this->list(mailerAddressKindEnum: $mailerAddressKindEnum)) > 0);
	}

	public function listAsCommaSeparatedString(
		MailerAddressKindEnum $mailerAddressKindEnum,
		int                   $maxLineLength,
		string                $defaultCharSet
	): string {
		if (!$this->has(mailerAddressKindEnum: $mailerAddressKindEnum)) {
			return '';
		}
		$array = [];
		foreach ($this->list(mailerAddressKindEnum: $mailerAddressKindEnum) as $mailerAddress) {
			$array[] = $mailerAddress->getFormattedAddressForMailer(
				maxLineLength: $maxLineLength,
				defaultCharSet: $defaultCharSet
			);
		}

		return implode(separator: ', ', array: $array);
	}

	public function getHeaderString(
		MailerAddressKindEnum $mailerAddressKindEnum,
		int                   $maxLineLength,
		string                $defaultCharSet
	): string {
		$listAsCommaSeparatedString = $this->listAsCommaSeparatedString(
			mailerAddressKindEnum: $mailerAddressKindEnum,
			maxLineLength: $maxLineLength,
			defaultCharSet: $defaultCharSet
		);

		return ($listAsCommaSeparatedString === '') ? '' : MailerHeader::createRaw(
			name: $mailerAddressKindEnum->value,
			value: $listAsCommaSeparatedString
		);
	}
}