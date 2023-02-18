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

use framework\mailer\attachment\MailerFileAttachment;
use framework\mailer\attachment\MailerStringAttachment;
use Throwable;

abstract class AbstractMail
{
	private bool $isSent = false;

	private MailerAddress $sender;
	private MailerAddress $fromAddress;
	private ?MailerAddress $confirmReadingToAddress = null;
	/** @var MailerAddress[] */
	private array $replyTo = [];
	/** @var MailerAddress[] */
	private array $recipients = [];

	private string $body;
	private string $alternativeBody = '';
	private bool $isHtmlBody = false;
	/** @var MailerFileAttachment[]|MailerStringAttachment[] */
	private array $attachments = [];
	private int $wordWrap = 0;
	/** @var MailerHeader[] */
	private array $customHeaders = [];

	protected function __construct(
		string                  $senderEmail,
		string                  $fromEmail,
		string                  $fromName,
		string                  $toEmail,
		string                  $toName,
		private string          $subject,
		private string          $charSet = MailerConstants::CHARSET_UTF8,
		private readonly string $encoding = MailerConstants::ENCODING_QUOTED_PRINTABLE,
		private readonly int    $priority = MailerConstants::PRIORITY_NORMAL
	) {
		$this->sender = MailerAddress::createSenderAddress(inputEmail: $senderEmail, inputName: '');
		$this->fromAddress = MailerAddress::createFromAddress(inputEmail: $fromEmail, inputName: $fromName);
		$this->addTo(inputEmail: $toEmail, inputName: $toName);
		$this->subject = trim(string: $this->subject);
		$this->charSet = strtolower(string: $this->charSet);
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
		$this->addValidatedAddress(mailerAddress: MailerAddress::createReplyToAddress(inputEmail: $inputEmail, inputName: $inputName));
	}

	public function addTo(string $inputEmail, string $inputName = ''): void
	{
		$this->addValidatedAddress(mailerAddress: MailerAddress::createToAddress(inputEmail: $inputEmail, inputName: $inputName));
	}

	public function addCC(string $inputEmail, string $inputName = ''): void
	{
		$this->addValidatedAddress(mailerAddress: MailerAddress::createCcAddress(inputEmail: $inputEmail, inputName: $inputName));
	}

	public function addBCC(string $inputEmail, string $inputName = ''): void
	{
		$this->addValidatedAddress(mailerAddress: MailerAddress::createBccAddress(inputEmail: $inputEmail, inputName: $inputName));
	}

	public function setConfirmReadingToAddress(string $inputEmail, string $inputName = ''): void
	{
		$this->confirmReadingToAddress = MailerAddress::createConfirmReadingToAddress(inputEmail: $inputEmail, inputName: $inputName);
	}

	public function addAttachment(MailerFileAttachment|MailerStringAttachment $mailerAttachment): void
	{
		$fileName = $mailerAttachment->fileName;
		if (array_key_exists(key: $fileName, array: $this->attachments)) {
			throw new MailerException(message: 'Attachment with fileName "' . $fileName . '" already exists.');
		}
		$this->attachments[$fileName] = $mailerAttachment;
	}

	public function setWordWrap(int $wordWrap): void
	{
		$this->wordWrap = $wordWrap;
	}

	public function addCustomHeader(string $name, string $value): void
	{
		$this->customHeaders[] = new MailerHeader(name: $name, value: $value);
	}

	public function send(): void
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
		$attachments = $this->attachments;

		$type = [];
		if ($alternativeExists) {
			$type[] = 'alt';
		}
		if ($this->inlineImageExists(attachments: $attachments)) {
			$type[] = 'inline';
		}
		if ($this->attachmentExists(attachments: $attachments)) {
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

		$to = [];
		$cc = [];
		$bcc = [];
		foreach ($this->recipients as $mailerAddress) {
			if ($mailerAddress->isToAddress()) {
				$to[] = $mailerAddress;
				continue;
			}
			if ($mailerAddress->isCcAddress()) {
				$cc[] = $mailerAddress;
				continue;
			}
			if ($mailerAddress->isBccAddress()) {
				$bcc[] = $mailerAddress;
			}
		}

		// Create body before headers in case body makes changes to headers (e.g. altering transfer encoding)
		$mimeBody = (new MailMimeBody(
			charSet: $charSet,
			contentType: $contentType,
			encoding: $encoding,
			messageType: $messageType,
			rawBody: $body,
			alternativeBody: $alternativeBody,
			boundary1: $boundary1,
			boundary2: $boundary2,
			boundary3: $boundary3,
			attachments: $attachments
		))->getBody();
		$mimeHeader = $this->createHeader(
			defaultCharSet: $charSet,
			from: $this->fromAddress,
			ccArr: $cc,
			bccArr: $bcc,
			replyToArr: $this->replyTo,
			uniqueId: $uniqueId,
			priority: $this->priority,
			confirmReadingToAddress: $this->confirmReadingToAddress,
			customHeaders: $this->customHeaders
		);
		$mimeHeader .= $this->getMailMIME(
			messageType: $messageType,
			contentType: $contentType,
			charSet: $charSet,
			encoding: $encoding,
			boundary1: $boundary1
		);

		$this->mailSend(
			defaultCharSet: $charSet,
			sender: $this->sender,
			to: $to,
			subject: $this->subject,
			mimeHeader: $mimeHeader,
			mimeBody: $mimeBody
		);
		$this->isSent = true;
	}

	private function addValidatedAddress(MailerAddress $mailerAddress): void
	{
		$email = $mailerAddress->getPunyEncodedEmail();
		if ($mailerAddress->isReplyToAddress()) {
			if (array_key_exists(key: $email, array: $this->replyTo)) {
				throw new MailerException(message: 'Reply-To address exists already: ' . $email);
			}

			$this->replyTo[$email] = $mailerAddress;

			return;
		}

		if (array_key_exists($email, $this->recipients)) {
			throw new MailerException(message: 'Recipient address exists already (' . $mailerAddress->kind . '): ' . $email);
		}
		$this->recipients[$email] = $mailerAddress;
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

	/**
	 * @param MailerFileAttachment[]|MailerStringAttachment[] $attachments
	 *
	 * @return bool
	 */
	private function inlineImageExists(array $attachments): bool
	{
		foreach ($attachments as $mailerAttachment) {
			if ($mailerAttachment->dispositionInline) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param MailerFileAttachment[]|MailerStringAttachment[] $attachments
	 *
	 * @return bool
	 */
	private function attachmentExists(array $attachments): bool
	{
		foreach ($attachments as $mailerAttachment) {
			if (!$mailerAttachment->dispositionInline) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string          $defaultCharSet
	 * @param MailerAddress   $sender
	 * @param MailerAddress[] $to
	 * @param string          $subject
	 * @param string          $mimeHeader
	 * @param string          $mimeBody
	 *
	 * @throws MailerException
	 */
	private function mailSend(
		string        $defaultCharSet,
		MailerAddress $sender,
		array         $to,
		string        $subject,
		string        $mimeHeader,
		string        $mimeBody
	): void {
		$toArr = [];
		foreach ($to as $mailerAddress) {
			$toArr[] = $mailerAddress->getFormattedAddressForMailer(defaultCharSet: $defaultCharSet);
		}
		$to = implode(separator: ', ', array: $toArr);

		$senderEmail = $sender->getPunyEncodedEmail();
		$this->mailPassThru(
			defaultCharSet: $defaultCharSet,
			to: $to,
			subject: $subject,
			body: $mimeBody,
			header: MailerFunctions::stripTrailingWSP(text: $mimeHeader) . MailerConstants::CRLF . MailerConstants::CRLF,
			params: MailerFunctions::isShellSafe(string: $senderEmail) ? '-f' . $senderEmail : null
		);
	}

	/**
	 * @param string             $defaultCharSet
	 * @param MailerAddress      $from
	 * @param MailerAddress[]    $ccArr
	 * @param MailerAddress[]    $bccArr
	 * @param MailerAddress[]    $replyToArr
	 * @param string             $uniqueId
	 * @param int                $priority
	 * @param MailerAddress|null $confirmReadingToAddress
	 * @param MailerHeader[]     $customHeaders
	 *
	 * @return string
	 */
	private function createHeader(
		string         $defaultCharSet,
		MailerAddress  $from,
		array          $ccArr,
		array          $bccArr,
		array          $replyToArr,
		string         $uniqueId,
		int            $priority,
		?MailerAddress $confirmReadingToAddress,
		array          $customHeaders
	): string {
		$fromArr = [$from];
		$result = MailerFunctions::headerLine(name: 'Date', value: date('D, j M Y H:i:s O'));
		$result .= $this->addrAppend(type: 'From', mailerAddresses: $fromArr, defaultCharSet: $defaultCharSet);
		if (count($ccArr) > 0) {
			$result .= $this->addrAppend(type: 'Cc', mailerAddresses: $ccArr, defaultCharSet: $defaultCharSet);
		}
		if (count($bccArr) > 0) {
			$result .= $this->addrAppend(type: 'Bcc', mailerAddresses: $bccArr, defaultCharSet: $defaultCharSet);
		}
		if (count($replyToArr) > 0) {
			$result .= $this->addrAppend(type: 'Reply-To', mailerAddresses: $replyToArr, defaultCharSet: $defaultCharSet);
		} else {
			$result .= $this->addrAppend(type: 'Reply-To', mailerAddresses: $fromArr, defaultCharSet: $defaultCharSet);
		}

		$result .= MailerFunctions::headerLine(name: 'Message-ID', value: '<' . $uniqueId . '@' . $_SERVER['SERVER_NAME'] . '>');
		$result .= MailerFunctions::headerLine(name: 'X-Priority', value: (string)$priority);

		if (!is_null($confirmReadingToAddress)) {
			$result .= MailerFunctions::headerLine(name: 'Disposition-Notification-To', value: '<' . $confirmReadingToAddress->getPunyEncodedEmail() . '>');
		}

		foreach ($customHeaders as $mailerHeader) {
			$result .= MailerFunctions::headerLine(
				name: $mailerHeader->getName(),
				value: MailerFunctions::encodeHeaderText(string: $mailerHeader->getValue(), defaultCharSet: $defaultCharSet)
			);
		}
		$result .= MailerFunctions::headerLine(name: 'MIME-Version', value: '1.0');

		return $result;
	}

	/**
	 * @param string          $type
	 * @param MailerAddress[] $mailerAddresses
	 * @param string          $defaultCharSet
	 *
	 * @return string
	 */
	private function addrAppend(string $type, array $mailerAddresses, string $defaultCharSet): string
	{
		$addresses = [];
		foreach ($mailerAddresses as $mailerAddress) {
			$addresses[] = $mailerAddress->getFormattedAddressForMailer(defaultCharSet: $defaultCharSet);
		}

		return $type . ': ' . implode(separator: ', ', array: $addresses) . MailerConstants::CRLF;
	}

	private function getMailMIME(
		string $messageType,
		string $contentType,
		string $charSet,
		string $encoding,
		string $boundary1
	): string {
		switch ($messageType) {
			case 'inline':
				$result = MailerFunctions::headerLine(name: 'Content-Type', value: MailerConstants::CONTENT_TYPE_MULTIPART_RELATED . ';');
				$result .= MailerFunctions::textLine(value: ' boundary="' . $boundary1 . '"');
				$isMultiPart = true;
				break;
			case 'attach':
			case 'inline_attach':
			case 'alt_attach':
			case 'alt_inline_attach':
				$result = MailerFunctions::headerLine(name: 'Content-Type', value: MailerConstants::CONTENT_TYPE_MULTIPART_MIXED . ';');
				$result .= MailerFunctions::textLine(value: ' boundary="' . $boundary1 . '"');
				$isMultiPart = true;
				break;
			case 'alt':
			case 'alt_inline':
				$result = MailerFunctions::headerLine(name: 'Content-Type', value: MailerConstants::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
				$result .= MailerFunctions::textLine(value: ' boundary="' . $boundary1 . '"');
				$isMultiPart = true;
				break;
			default:
				// Catches case 'plain': and case '':
				$result = MailerFunctions::textLine(value: 'Content-Type: ' . $contentType . '; charset=' . $charSet);
				$isMultiPart = false;
				break;
		}
		// RFC1341 part 5 says 7bit is assumed if not specified
		if (MailerConstants::ENCODING_7BIT === $encoding) {
			return $result;
		}

		// RFC 2045 section 6.4 says multipart MIME parts may only use 7bit, 8bit or binary CTE
		if (!$isMultiPart) {
			$result .= MailerFunctions::headerLine(name: 'Content-Transfer-Encoding', value: $encoding);

			return $result;
		}

		if (MailerConstants::ENCODING_8BIT === $encoding) {
			$result .= MailerFunctions::headerLine(name: 'Content-Transfer-Encoding', value: MailerConstants::ENCODING_8BIT);

			return $result;
		}

		// The only remaining alternatives are quoted-printable and base64, which are both 7bit compatible
		return $result;
	}

	private function mailPassThru(
		string  $defaultCharSet,
		string  $to,
		string  $subject,
		string  $body,
		string  $header,
		?string $params
	): void {
		$subject = MailerFunctions::encodeHeaderText(
			string: MailerFunctions::secureHeader(string: $subject),
			defaultCharSet: $defaultCharSet
		);
		if (is_null(value: $params)) {
			$result = mail(
				to: $to,
				subject: $subject,
				message: $body,
				additional_headers: $header
			);
		} else {
			$result = mail(
				to: $to,
				subject: $subject,
				message: $body,
				additional_headers: $header,
				additional_params: $params
			);
		}
		if ($result === false) {
			throw new MailerException(message: 'Could not instantiate mail function.');
		}
	}
}