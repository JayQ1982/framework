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
 * @author    actra AG (for this class) <framework@actra.ch>
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
	private const KIND_SENDER = 'Sender';
	private const KIND_FROM = 'From';
	private const KIND_CONFIRM_READING_TO = 'ConfirmReadingTo';
	private const KIND_TO = 'To';
	private const KIND_CC = 'Cc';
	private const KIND_BCC = 'Bcc';
	private const KIND_REPLY_TO = 'Reply-To';

	private string $punyEncodedDomain;
	private string $emailNamePart;
	private string $addressName;

	private function __construct(
		private string $kind,
		string         $inputEmail,
		string         $inputName
	) {
		$inputEmail = mb_strtolower(string: trim(string: $inputEmail));
		$atPos = strrpos(haystack: $inputEmail, needle: '@');
		if ($atPos === false) {
			throw new MailerException(message: 'Missing @-sign (' . $kind . '): ' . $inputEmail);
		}
		$domain = substr(string: $inputEmail, offset: ++$atPos);

		$this->punyEncodedDomain = MailerFunctions::punyEncodeDomain(domain: $domain);
		$this->emailNamePart = substr(string: $inputEmail, offset: 0, length: $atPos - 1);

		if (!MailerFunctions::validateAddress(address: $this->getPunyEncodedEmail())) {
			throw new MailerException(message: 'Invalid address (' . $kind . '): ' . $this->getPunyEncodedEmail());
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
			kind: MailerAddress::KIND_SENDER,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createFromAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			kind: MailerAddress::KIND_FROM,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createConfirmReadingToAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			kind: MailerAddress::KIND_CONFIRM_READING_TO,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createToAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			kind: MailerAddress::KIND_TO,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createCcAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			kind: MailerAddress::KIND_CC,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createBccAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			kind: MailerAddress::KIND_BCC,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public static function createReplyToAddress(string $inputEmail, string $inputName): MailerAddress
	{
		return new MailerAddress(
			kind: MailerAddress::KIND_REPLY_TO,
			inputEmail: $inputEmail,
			inputName: $inputName
		);
	}

	public function getKind(): string
	{
		return $this->kind;
	}

	public function isFromAddress(): bool
	{
		return $this->kind === MailerAddress::KIND_FROM;
	}

	public function isConfirmReadingToAddress(): bool
	{
		return $this->kind === MailerAddress::KIND_CONFIRM_READING_TO;
	}

	public function isToAddress(): bool
	{
		return $this->kind === MailerAddress::KIND_TO;
	}

	public function isCcAddress(): bool
	{
		return $this->kind === MailerAddress::KIND_CC;
	}

	public function isBccAddress(): bool
	{
		return $this->kind === MailerAddress::KIND_BCC;
	}

	public function isReplyToAddress(): bool
	{
		return $this->kind === MailerAddress::KIND_REPLY_TO;
	}

	public function getPunyEncodedEmail(): string
	{
		return $this->emailNamePart . '@' . $this->punyEncodedDomain;
	}

	public function getName(): string
	{
		return $this->addressName;
	}

	public function getFormattedAddressForMailer(string $defaultCharSet): string
	{
		$preparedEmailAddress = MailerFunctions::secureHeader(string: $this->getPunyEncodedEmail());
		if ($this->addressName === '') {
			return $preparedEmailAddress;
		}

		return implode(
			separator: ' ',
			array: [
				MailerFunctions::encodeHeaderPhrase(
					string: MailerFunctions::secureHeader(string: $this->addressName),
					defaultCharSet: $defaultCharSet
				),
				' <' . $preparedEmailAddress . '>',
			]
		);
	}
}