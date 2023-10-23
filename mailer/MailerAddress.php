<?php
/**
 * Integral adaptive work to derived PHPMailer classes by Actra AG.
 * For original library, please see:
 *
 * @see       https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author    Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author    Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author    Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author    Brent R. Matzelle (original founder)
 * @author    Actra AG (for this class) <framework@actra.ch>
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @copyright 2022 Actra AG
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note      This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace framework\mailer;

class MailerAddress
{
	private string $punyEncodedDomain;
	private string $emailNamePart;
	private string $addressName;

	private function __construct(
		public readonly MailerAddressKindEnum $mailerAddressKindEnum,
		string                                $inputEmail,
		string                                $inputName
	) {
		$inputEmail = mb_strtolower(string: trim(string: $inputEmail));
		$atPos = strrpos(haystack: $inputEmail, needle: '@');
		if ($atPos === false) {
			throw new MailerException(message: 'Missing @-sign (' . $mailerAddressKindEnum->value . '): ' . $inputEmail);
		}
		$domain = substr(string: $inputEmail, offset: ++$atPos);

		$this->punyEncodedDomain = MailerFunctions::punyEncodeDomain(domain: $domain);
		$this->emailNamePart = substr(string: $inputEmail, offset: 0, length: $atPos - 1);

		if (!MailerFunctions::validateAddress(address: $this->getPunyEncodedEmail())) {
			throw new MailerException(message: 'Invalid address (' . $mailerAddressKindEnum->value . '): ' . $this->getPunyEncodedEmail());
		}

		$this->addressName = trim(string: preg_replace(
			pattern: '/[\r\n]+/',
			replacement: '',
			subject: $inputName
		)); // Strip breaks and trim
	}

	public static function createSenderAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			mailerAddressKindEnum: MailerAddressKindEnum::KIND_SENDER,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createFromAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			mailerAddressKindEnum: MailerAddressKindEnum::KIND_FROM,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createConfirmReadingToAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			mailerAddressKindEnum: MailerAddressKindEnum::KIND_CONFIRM_READING_TO,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createToAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			mailerAddressKindEnum: MailerAddressKindEnum::KIND_TO,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createCcAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			mailerAddressKindEnum: MailerAddressKindEnum::KIND_CC,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createBccAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			mailerAddressKindEnum: MailerAddressKindEnum::KIND_BCC,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createReplyToAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			mailerAddressKindEnum: MailerAddressKindEnum::KIND_REPLY_TO,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public function getPunyEncodedEmail(): string
	{
		return $this->emailNamePart . '@' . $this->punyEncodedDomain;
	}

	public function getName(): string
	{
		return $this->addressName;
	}

	public function getFormattedAddressForMailer(
		int    $maxLineLength,
		string $defaultCharSet
	): string {
		$preparedEmailAddress = MailerFunctions::secureHeader(string: $this->getPunyEncodedEmail());
		if ($this->addressName === '') {
			return $preparedEmailAddress;
		}

		return implode(
			separator: ' ',
			array: [
				MailerFunctions::encodeHeaderPhrase(
					string: MailerFunctions::secureHeader(string: $this->addressName),
					maxLineLength: $maxLineLength,
					defaultCharSet: $defaultCharSet
				),
				'<' . $preparedEmailAddress . '>',
			]
		);
	}
}