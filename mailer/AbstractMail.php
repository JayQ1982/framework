<?php
/**
 * Derived work from PHPMailer, reduced to the code needed by this Framework.
 * For original full library, please see:
 *
 * @see       https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author    Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author    Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author    Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author    Brent R. Matzelle (original founder)
 * @author    Actra AG (for derived, reduced code) <framework@actra.ch>
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

use framework\mailer\attachment\MailerAttachmentCollection;
use framework\mailer\attachment\MailerFileAttachment;
use framework\mailer\attachment\MailerStringAttachment;
use Throwable;

abstract class AbstractMail
{
	private bool $isSent = false;

	public readonly MailerAddress $sender;
	public readonly MailerAddress $fromAddress;
	private ?MailerAddress $confirmReadingToAddress = null;
	public readonly MailerAddressCollection $mailerAddressCollection;

	private string $body;
	private string $alternativeBody = '';
	private bool $isHtmlBody = false;
	private MailerAttachmentCollection $mailerAttachmentCollection;
	private int $wordWrap = 0;
	private MailerHeaderCollection $customHeaders;
	private readonly string $subject;

	protected function __construct(
		string                  $senderEmail,
		string                  $fromEmail,
		string                  $fromName,
		string                  $toEmail,
		string                  $toName,
		string                  $subject,
		public readonly string  $charSet = MailerConstants::CHARSET_UTF8,
		private readonly string $encoding = MailerConstants::ENCODING_QUOTED_PRINTABLE,
		private readonly int    $priority = MailerConstants::PRIORITY_NORMAL
	) {
		$this->sender = MailerAddress::createSenderAddress(inputEmail: $senderEmail, inputName: '');
		$this->fromAddress = MailerAddress::createFromAddress(inputEmail: $fromEmail, inputName: $fromName);
		$this->mailerAddressCollection = new MailerAddressCollection();
		$this->mailerAttachmentCollection = new MailerAttachmentCollection();
		$this->customHeaders = new MailerHeaderCollection();
		$this->addTo(inputEmail: $toEmail, inputName: $toName);
		$this->subject = trim(string: $subject);
		if (!in_array(needle: $this->charSet, haystack: MailerConstants::CHARSET_LIST)) {
			throw new MailerException(message: 'Invalid charset "' . $this->charSet . '". See MailerConstants::CHARSET_LIST[].');
		}
		if (!in_array(needle: $this->encoding, haystack: MailerConstants::ENCODING_LIST)) {
			throw new MailerException(message: 'Invalid encoding "' . $this->encoding . '". See MailerConstants::ENCODING_LIST[].');
		}
		if (!in_array(needle: $this->priority, haystack: MailerConstants::PRIORITY_LIST)) {
			throw new MailerException(message: 'Invalid priority "' . $this->priority . '". See MailerConstants::PRIORITY_LIST[].');
		}
	}

	protected function setTextBody(string $textBody): void
	{
		$this->body = trim(string: $textBody);
	}

	protected function setHtmlBody(string $htmlBody, string $alternativeBody): void
	{
		$this->body = trim(string: $htmlBody);
		$this->alternativeBody = trim(string: $alternativeBody);
		$this->isHtmlBody = true;
	}

	public function addReplyTo(string $inputEmail, string $inputName = ''): void
	{
		$this->mailerAddressCollection->addItem(mailerAddress: MailerAddress::createReplyToAddress(
			inputEmail: $inputEmail,
			inputName: $inputName
		));
	}

	public function addTo(string $inputEmail, string $inputName = ''): void
	{
		$this->mailerAddressCollection->addItem(mailerAddress: MailerAddress::createToAddress(
			inputEmail: $inputEmail,
			inputName: $inputName
		));
	}

	public function addCC(string $inputEmail, string $inputName = ''): void
	{
		$this->mailerAddressCollection->addItem(mailerAddress: MailerAddress::createCcAddress(
			inputEmail: $inputEmail,
			inputName: $inputName
		));
	}

	public function addBCC(string $inputEmail, string $inputName = ''): void
	{
		$this->mailerAddressCollection->addItem(mailerAddress: MailerAddress::createBccAddress(
			inputEmail: $inputEmail,
			inputName: $inputName
		));
	}

	public function setConfirmReadingToAddress(string $inputEmail, string $inputName = ''): void
	{
		$this->confirmReadingToAddress = MailerAddress::createConfirmReadingToAddress(inputEmail: $inputEmail, inputName: $inputName);
	}

	public function addAttachment(MailerFileAttachment|MailerStringAttachment $mailerAttachment): void
	{
		$this->mailerAttachmentCollection->addItem(mailerAttachment: $mailerAttachment);
	}

	public function setWordWrap(int $wordWrap): void
	{
		$this->wordWrap = $wordWrap;
	}

	public function addCustomHeader(
		string $name,
		string $value,
		int    $maxLineLength
	): void {
		$this->customHeaders->addItem(mailerHeader: MailerHeader::createEncodedHeaderText(
			name: $name,
			value: $value,
			maxLineLength: $maxLineLength,
			defaultCharSet: $this->charSet
		));
	}

	public function send(AbstractMailer $abstractMailer = new MailMailer()): void
	{
		if ($this->isSent) {
			throw new MailerException(message: 'You cannot send the same email multiple times.');
		}

		$body = $this->body;
		if ($body === '') {
			throw new MailerException(message: 'Message body is empty');
		}
		$alternativeBody = $this->alternativeBody;
		$alternativeExists = ($alternativeBody !== '');
		$mailerAttachmentCollection = $this->mailerAttachmentCollection;

		$type = [];
		if ($alternativeExists) {
			$type[] = 'alt';
		}
		if ($mailerAttachmentCollection->hasInlineImages()) {
			$type[] = 'inline';
		}
		if ($mailerAttachmentCollection->hasAttachments()) {
			$type[] = 'attach';
		}
		$messageType = implode(separator: '_', array: $type);
		//The 'plain' messageType refers to the message having a single body element, not that it is plain-text
		$messageType = ($messageType === '') ? 'plain' : $messageType;
		$wordWrap = $this->wordWrap;
		$charSet = $this->charSet;
		if ($wordWrap > 0) {
			switch ($messageType) {
				case 'alt':
				case 'alt_inline':
				case 'alt_attach':
				case 'alt_inline_attach':
					$alternativeBody = MailerFunctions::wrapText(message: $alternativeBody, length: $wordWrap, charSet: $charSet, qp_mode: false);
					break;
				default:
					$body = MailerFunctions::wrapText(message: $body, length: $wordWrap, charSet: $charSet, qp_mode: false);
					break;
			}
		}

		if ($alternativeExists) {
			$contentType = MailerConstants::CONTENT_TYPE_MULTIPART_ALTERNATIVE;
		} else if ($this->isHtmlBody) {
			$contentType = MailerConstants::CONTENT_TYPE_TEXT_HTML;
		} else {
			$contentType = MailerConstants::CONTENT_TYPE_PLAINTEXT;
		}

		$encoding = $this->encoding;
		$uniqueId = $this->generateId();
		$boundary1 = 'b1_' . $uniqueId;
		$boundary2 = 'b2_' . $uniqueId;
		$boundary3 = 'b3_' . $uniqueId;

		$mailerAddressCollection = $this->mailerAddressCollection;
		$maxLineLength = $abstractMailer->getMaxLineLength();
		$abstractMailer->sendMail(
			abstractMail: $this,
			mailMimeHeader: new MailMimeHeader(
				abstractMailer: $abstractMailer,
				subjectForHeader: $this->getSubjectForHeader(maxLineLength: $maxLineLength),
				defaultCharSet: $charSet,
				fromAddress: $this->fromAddress,
				mailerAddressCollection: $mailerAddressCollection,
				uniqueId: $uniqueId,
				priority: $this->priority,
				confirmReadingToAddress: $this->confirmReadingToAddress,
				customHeaders: $this->customHeaders,
				messageType: $messageType,
				contentType: $contentType,
				charSet: $charSet,
				encoding: $encoding,
				boundary1: $boundary1
			),
			mailMimeBody: new MailMimeBody(
				maxLineLength: $maxLineLength,
				charSet: $charSet,
				contentType: $contentType,
				encoding: $encoding,
				messageType: $messageType,
				rawBody: $body,
				alternativeBody: $alternativeBody,
				boundary1: $boundary1,
				boundary2: $boundary2,
				boundary3: $boundary3,
				mailerAttachmentCollection: $mailerAttachmentCollection
			)
		);
		$this->isSent = true;
	}

	private function generateId(): string
	{
		$bytes = '';
		try {
			$bytes = random_bytes(length: 32); // 32 bytes = 256 bits
		} catch (Throwable) {
			//Do nothing
		}
		if ($bytes === '') {
			// We failed to produce a proper random string, so make do.
			// Use a hash to force the length to the same as the other methods
			$bytes = hash(algo: 'sha256', data: uniqid(prefix: (string)mt_rand(), more_entropy: true), binary: true);
		}

		// We don't care about messing up base64 format here, just want a random string
		return str_replace(search: ['=', '+', '/'], replace: '', subject: base64_encode(string: hash(algo: 'sha256', data: $bytes, binary: true)));
	}

	public function getSubjectForHeader(int $maxLineLength): string
	{
		return MailerFunctions::encodeHeaderText(
			string: MailerFunctions::secureHeader(string: $this->subject),
			maxLineLength: $maxLineLength,
			defaultCharSet: $this->charSet
		);
	}
}