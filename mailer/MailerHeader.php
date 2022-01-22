<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer;

class MailerHeader
{
	public function __construct(
		private string $name,
		private string $value
	) {
		$name = trim(string: $name);
		$value = trim(string: $value);

		// Ensure name is not empty, and that neither name nor value contain line breaks
		if ($name === '' || strpbrk(string: $name . $value, characters: "\r\n") !== false) {
			throw new MailerException(message: 'Invalid header name or value');
		}

		$this->name = $name;
		$this->value = $value;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValue(): string
	{
		return $this->value;
	}
}