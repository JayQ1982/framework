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

use framework\common\StringUtils;

class MailMimeHeader
{
	private array $headerItems = [];

	public function __construct(
		AbstractMailer          $abstractMailer,
		string                  $subjectForHeader,
		string                  $defaultCharSet,
		MailerAddress           $fromAddress,
		MailerAddressCollection $mailerAddressCollection,
		string                  $uniqueId,
		int                     $priority,
		?MailerAddress          $confirmReadingToAddress,
		MailerHeaderCollection  $customHeaders,
		string                  $messageType,
		string                  $contentType,
		string                  $charSet,
		string                  $encoding,
		string                  $boundary1
	) {
		$maxLineLength = $abstractMailer->getMaxLineLength();
		$this->addHeaderItemIfNotEmpty(item: MailerHeader::createRaw(
			name: 'Date',
			value: date(format: 'r')
		));
		$this->addHeaderItemIfNotEmpty(item: MailerHeader::createRaw(
			name: MailerAddressKindEnum::KIND_FROM->value,
			value: $fromAddress->getFormattedAddressForMailer(
				maxLineLength: $maxLineLength,
				defaultCharSet: $defaultCharSet
			)
		));
		if ($abstractMailer->headerHasTo()) {
			$this->addHeaderItemIfNotEmpty(item: $mailerAddressCollection->getHeaderString(
				mailerAddressKindEnum: MailerAddressKindEnum::KIND_TO,
				maxLineLength: $maxLineLength,
				defaultCharSet: $defaultCharSet
			));
		}
		$this->addHeaderItemIfNotEmpty(item: $mailerAddressCollection->getHeaderString(
			mailerAddressKindEnum: MailerAddressKindEnum::KIND_CC,
			maxLineLength: $maxLineLength,
			defaultCharSet: $defaultCharSet
		));
		$this->addHeaderItemIfNotEmpty(item: $mailerAddressCollection->getHeaderString(
			mailerAddressKindEnum: MailerAddressKindEnum::KIND_BCC,
			maxLineLength: $maxLineLength,
			defaultCharSet: $defaultCharSet
		));
		if ($mailerAddressCollection->has(mailerAddressKindEnum: MailerAddressKindEnum::KIND_REPLY_TO)) {
			$this->addHeaderItemIfNotEmpty(item: $mailerAddressCollection->getHeaderString(
				mailerAddressKindEnum: MailerAddressKindEnum::KIND_REPLY_TO,
				maxLineLength: $maxLineLength,
				defaultCharSet: $defaultCharSet
			));
		} else {
			$this->addHeaderItemIfNotEmpty(item: MailerHeader::createRaw(
				name: MailerAddressKindEnum::KIND_REPLY_TO->value,
				value: $fromAddress->getFormattedAddressForMailer(
					maxLineLength: $maxLineLength,
					defaultCharSet: $defaultCharSet
				)
			));
		}
		if ($abstractMailer->headerHasSubject()) {
			$this->addHeaderItemIfNotEmpty(item: MailerHeader::createRaw(
				name: 'Subject',
				value: $subjectForHeader
			));
		}
		$this->addHeaderItemIfNotEmpty(item: MailerHeader::createRaw(
			name: 'Message-ID',
			value: '<' . $uniqueId . '@' . $abstractMailer->getServerName() . '>'
		));
		$this->addHeaderItemIfNotEmpty(item: MailerHeader::createRaw(
			name: 'X-Mailer',
			value: 'PHP/' . phpversion()
		));
		$this->addHeaderItemIfNotEmpty(item: MailerHeader::createRaw(
			name: 'X-Priority',
			value: (string)$priority
		));
		if (!is_null(value: $confirmReadingToAddress)) {
			$this->addHeaderItemIfNotEmpty(item: MailerHeader::createRaw(
				name: 'Disposition-Notification-To',
				value: $confirmReadingToAddress->getFormattedAddressForMailer(
					maxLineLength: $maxLineLength,
					defaultCharSet: $defaultCharSet
				)
			));
		}
		foreach ($customHeaders->list() as $mailerHeader) {
			$this->addHeaderItemIfNotEmpty(item: $mailerHeader->get());
		}
		$this->addHeaderItemIfNotEmpty(item: MailerHeader::createRaw(
			name: 'MIME-Version',
			value: '1.0'
		));
		$this->addHeaderItemIfNotEmpty(item: $this->getMailMIME(
			messageType: $messageType,
			contentType: $contentType,
			charSet: $charSet,
			encoding: $encoding,
			boundary1: $boundary1
		));
	}

	private function addHeaderItemIfNotEmpty(string $item): void
	{
		if ($item === '') {
			return;
		}
		$this->headerItems[] = $item;
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
				$result = MailerHeader::createRaw(name: 'Content-Type', value: MailerConstants::CONTENT_TYPE_MULTIPART_RELATED . ';');
				$result .= MailerFunctions::textLine(value: ' boundary="' . $boundary1 . '"');
				$isMultiPart = true;
				break;
			case 'attach':
			case 'inline_attach':
			case 'alt_attach':
			case 'alt_inline_attach':
				$result = MailerHeader::createRaw(name: 'Content-Type', value: MailerConstants::CONTENT_TYPE_MULTIPART_MIXED . ';');
				$result .= MailerFunctions::textLine(value: ' boundary="' . $boundary1 . '"');
				$isMultiPart = true;
				break;
			case 'alt':
			case 'alt_inline':
				$result = MailerHeader::createRaw(name: 'Content-Type', value: MailerConstants::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
				$result .= MailerFunctions::textLine(value: ' boundary="' . $boundary1 . '"');
				$isMultiPart = true;
				break;
			default:
				// Catches case 'plain': and case '':
				$result = MailerHeader::createRaw(name: 'Content-Type', value: $contentType . '; charset=' . $charSet);
				$isMultiPart = false;
				break;
		}
		// RFC1341 part 5 says 7bit is assumed if not specified
		if (MailerConstants::ENCODING_7BIT === $encoding) {
			return $result;
		}

		// RFC 2045 section 6.4 says multipart MIME parts may only use 7bit, 8bit or binary CTE
		if (!$isMultiPart) {
			$result .= MailerHeader::createRaw(name: 'Content-Transfer-Encoding', value: $encoding);

			return $result;
		}

		if (MailerConstants::ENCODING_8BIT === $encoding) {
			$result .= MailerHeader::createRaw(name: 'Content-Transfer-Encoding', value: MailerConstants::ENCODING_8BIT);

			return $result;
		}

		// The only remaining alternatives are quoted-printable and base64, which are both 7bit compatible
		return $result;
	}

	public function getMimeHeader(): string
	{
		return MailerFunctions::stripTrailingWSP(text: implode(separator: StringUtils::IMPLODE_DEFAULT_SEPARATOR, array: $this->headerItems));
	}
}