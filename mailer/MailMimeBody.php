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
use framework\mailer\attachment\MailerAttachmentCollection;
use framework\mailer\attachment\MailerStringAttachment;

class MailMimeBody
{
	private string $body;
	private string $defaultCharSet;
	private string $defaultContentType;
	private string $defaultEncoding;

	public function __construct(
		int                        $maxLineLength,
		string                     $charSet,
		string                     $contentType,
		string                     $encoding,
		string                     $messageType,
		string                     $rawBody,
		string                     $alternativeBody,
		string                     $boundary1,
		string                     $boundary2,
		string                     $boundary3,
		MailerAttachmentCollection $mailerAttachmentCollection
	) {
		$this->defaultCharSet = $charSet;
		$this->defaultContentType = $contentType;
		$this->defaultEncoding = $encoding;

		$bodyEncoding = $encoding;
		$bodyCharSet = $charSet;

		// Can we do a 7-bit downgrade?
		if (MailerConstants::ENCODING_8BIT === $bodyEncoding && !MailerFunctions::has8bitChars(text: $rawBody)) {
			$bodyEncoding = MailerConstants::ENCODING_7BIT;

			// All ISO 8859, Windows codepage and UTF-8 charsets are ascii compatible up to 7-bit
			$bodyCharSet = MailerConstants::CHARSET_ASCII;
		}

		// If lines are too long, and we're not already using an encoding that will shorten them, change to quoted-printable transfer encoding for the body part only
		if (MailerConstants::ENCODING_BASE64 !== $encoding && $this->hasLineLongerThanMax(str: $rawBody)) {
			$bodyEncoding = MailerConstants::ENCODING_QUOTED_PRINTABLE;
		}

		$altBodyEncoding = $encoding;
		$altBodyCharSet = $charSet;

		// Can we do a 7-bit downgrade?
		if (MailerConstants::ENCODING_8BIT === $altBodyEncoding && !MailerFunctions::has8bitChars(text: $alternativeBody)) {
			$altBodyEncoding = MailerConstants::ENCODING_7BIT;

			// All ISO 8859, Windows codepage and UTF-8 charsets are ascii compatible up to 7-bit
			$altBodyCharSet = MailerConstants::CHARSET_ASCII;
		}

		// If lines are too long, and we're not already using an encoding that will shorten them, change to quoted-printable transfer encoding for the alt body part only
		if (MailerConstants::ENCODING_BASE64 !== $altBodyEncoding && $this->hasLineLongerThanMax(str: $alternativeBody)) {
			$altBodyEncoding = MailerConstants::ENCODING_QUOTED_PRINTABLE;
		}

		// Use this as a preamble in all multipart message types
		$mimePreamble = 'This is a multi-part message in MIME format.' . MailerConstants::CRLF . MailerConstants::CRLF;

		$body = '';
		switch ($messageType) {
			case 'inline':
				$body .= $mimePreamble;
				$body .= $this->getBoundary(
					boundary: $boundary1,
					charSet: $bodyCharSet,
					contentType: '',
					encoding: $bodyEncoding,
					stringToEncode: $rawBody
				);
				$body .= $this->attachAll(
					mailerAttachmentCollection: $mailerAttachmentCollection,
					maxLineLength: $maxLineLength,
					dispositionInline: 'inline',
					boundary: $boundary1
				);
				break;
			case 'attach':
				$body .= $mimePreamble;
				$body .= $this->getBoundary(
					boundary: $boundary1,
					charSet: $bodyCharSet,
					contentType: '',
					encoding: $bodyEncoding,
					stringToEncode: $rawBody
				);
				$body .= $this->attachAll(
					mailerAttachmentCollection: $mailerAttachmentCollection,
					maxLineLength: $maxLineLength,
					dispositionInline: 'attachment',
					boundary: $boundary1
				);
				break;
			case 'inline_attach':
				$body .= $mimePreamble;
				$body .= MailerFunctions::textLine(value: '--' . $boundary1);
				$body .= MailerHeader::createRaw(name: 'Content-Type', value: MailerConstants::CONTENT_TYPE_MULTIPART_RELATED . ';');
				$body .= MailerFunctions::textLine(value: ' boundary="' . $boundary2 . '";');
				$body .= MailerFunctions::textLine(value: ' type="' . MailerConstants::CONTENT_TYPE_TEXT_HTML . '"');
				$body .= MailerConstants::CRLF;
				$body .= $this->getBoundary(
					boundary: $boundary2,
					charSet: $bodyCharSet,
					contentType: '',
					encoding: $bodyEncoding,
					stringToEncode: $rawBody
				);
				$body .= $this->attachAll(
					mailerAttachmentCollection: $mailerAttachmentCollection,
					maxLineLength: $maxLineLength,
					dispositionInline: 'inline',
					boundary: $boundary2
				);
				$body .= MailerConstants::CRLF;
				$body .= $this->attachAll(
					mailerAttachmentCollection: $mailerAttachmentCollection,
					maxLineLength: $maxLineLength,
					dispositionInline: 'attachment',
					boundary: $boundary1
				);
				break;
			case 'alt':
				$body .= $mimePreamble;
				$body .= $this->getBoundary(
					boundary: $boundary1,
					charSet: $altBodyCharSet,
					contentType: MailerConstants::CONTENT_TYPE_PLAINTEXT,
					encoding: $altBodyEncoding,
					stringToEncode: $alternativeBody
				);
				$body .= $this->getBoundary(
					boundary: $boundary1,
					charSet: $bodyCharSet,
					contentType: MailerConstants::CONTENT_TYPE_TEXT_HTML,
					encoding: $bodyEncoding,
					stringToEncode: $rawBody
				);
				$body .= $this->endBoundary($boundary1);
				break;
			case 'alt_inline':
				$body .= $mimePreamble;
				$body .= $this->inlineAlternativeBody(
					firstBoundary: $boundary1,
					secondBoundary: $boundary2,
					alternativeBody: $alternativeBody,
					alternativeBodyCharSet: $altBodyCharSet,
					alternativeBodyEncoding: $altBodyEncoding,
					maxLineLength: $maxLineLength,
					bodyCharSet: $bodyCharSet,
					bodyEncoding: $bodyEncoding,
					rawBody: $rawBody,
					mailerAttachmentCollection: $mailerAttachmentCollection
				);
				break;
			case 'alt_attach':
				$body .= $mimePreamble;
				$body .= MailerFunctions::textLine(value: '--' . $boundary1);
				$body .= MailerHeader::createRaw(name: 'Content-Type', value: MailerConstants::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
				$body .= MailerFunctions::textLine(value: ' boundary="' . $boundary2 . '"');
				$body .= MailerConstants::CRLF;
				$body .= $this->getBoundary(
					boundary: $boundary2,
					charSet: $altBodyCharSet,
					contentType: MailerConstants::CONTENT_TYPE_PLAINTEXT,
					encoding: $altBodyEncoding,
					stringToEncode: $alternativeBody
				);
				$body .= $this->getBoundary(
					boundary: $boundary2,
					charSet: $bodyCharSet,
					contentType: MailerConstants::CONTENT_TYPE_TEXT_HTML,
					encoding: $bodyEncoding,
					stringToEncode: $rawBody
				);
				$body .= $this->endBoundary(boundary: $boundary2);
				$body .= MailerConstants::CRLF;
				$body .= $this->attachAll(
					mailerAttachmentCollection: $mailerAttachmentCollection,
					maxLineLength: $maxLineLength,
					dispositionInline: 'attachment',
					boundary: $boundary1
				);
				break;
			case 'alt_inline_attach':
				$body .= $mimePreamble;
				$body .= MailerFunctions::textLine(value: '--' . $boundary1);
				$body .= MailerHeader::createRaw(name: 'Content-Type', value: MailerConstants::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
				$body .= MailerFunctions::textLine(value: ' boundary="' . $boundary2 . '"');
				$body .= MailerConstants::CRLF;
				$body .= $this->inlineAlternativeBody(
					firstBoundary: $boundary2,
					secondBoundary: $boundary3,
					alternativeBody: $alternativeBody,
					alternativeBodyCharSet: $altBodyCharSet,
					alternativeBodyEncoding: $altBodyEncoding,
					maxLineLength: $maxLineLength,
					bodyCharSet: $bodyCharSet,
					bodyEncoding: $bodyEncoding,
					rawBody: $rawBody,
					mailerAttachmentCollection: $mailerAttachmentCollection
				);
				$body .= MailerConstants::CRLF;
				$body .= $this->attachAll(
					mailerAttachmentCollection: $mailerAttachmentCollection,
					maxLineLength: $maxLineLength,
					dispositionInline: 'attachment',
					boundary: $boundary1
				);
				break;
			default:
				// Catch case 'plain' and case '', applies to simple `text/plain` and `text/html` body content types
				$body .= MailerFunctions::encodeString(string: $rawBody, encoding: $encoding);
				break;
		}

		$this->body = $body;
	}

	public function getMimeBody(): string
	{
		return $this->body;
	}

	private function hasLineLongerThanMax(string $str): bool
	{
		return (preg_match(
				pattern: '/^(.{' . (MailerConstants::MAX_LINE_LENGTH + strlen(MailerConstants::CRLF)) . ',})/m',
				subject: $str
			) === 1
		);
	}

	private function getBoundary(
		string $boundary,
		string $charSet,
		string $contentType,
		string $encoding,
		string $stringToEncode
	): string {
		$result = '';
		if ($charSet === '') {
			$charSet = $this->defaultCharSet;
		}
		if ($contentType === '') {
			$contentType = $this->defaultContentType;
		}
		if ($encoding === '') {
			$encoding = $this->defaultEncoding;
		}
		$result .= MailerFunctions::textLine(value: '--' . $boundary);
		$result .= 'Content-Type: ' . $contentType . '; charset=' . $charSet;
		$result .= MailerConstants::CRLF;

		// RFC1341 part 5 says 7bit is assumed if not specified
		if (MailerConstants::ENCODING_7BIT !== $encoding) {
			$result .= MailerHeader::createRaw(name: 'Content-Transfer-Encoding', value: $encoding);
		}
		$result .= MailerConstants::CRLF;
		$result .= MailerFunctions::encodeString(string: $stringToEncode, encoding: $encoding);
		$result .= MailerConstants::CRLF;

		return $result;
	}

	private function attachAll(
		MailerAttachmentCollection $mailerAttachmentCollection,
		int                        $maxLineLength,
		bool                       $dispositionInline,
		string                     $boundary
	): string {
		$mime = [];
		foreach ($mailerAttachmentCollection->list() as $attachment) {
			if ($attachment->dispositionInline && !$dispositionInline) {
				continue;
			}
			$string = '';
			$path = '';
			$isStringAttachment = ($attachment instanceof MailerStringAttachment);
			if ($isStringAttachment) {
				$string = $attachment->contentString;
			} else {
				$path = $attachment->path;
			}

			$fileName = $attachment->fileName;
			$encoding = $attachment->encoding;
			$type = $attachment->type;

			$mime[] = '--' . $boundary . MailerConstants::CRLF;
			$mime[] = 'Content-Type: ' . $type . '; name=' . $this->quotedString(
					string: MailerFunctions::encodeHeaderText(
						string: MailerFunctions::secureHeader(string: $fileName),
						maxLineLength: $maxLineLength,
						defaultCharSet: $this->defaultCharSet
					)
				) . MailerConstants::CRLF;
			// RFC1341 part 5 says 7bit is assumed if not specified
			if (MailerConstants::ENCODING_7BIT !== $encoding) {
				$mime[] = 'Content-Transfer-Encoding: ' . $encoding . MailerConstants::CRLF;
			}

			// Only set Content-IDs on inline attachments
			if ($dispositionInline) {
				$mime[] = 'Content-ID: <' . MailerFunctions::encodeHeaderText(
						string: MailerFunctions::secureHeader(string: $fileName),
						maxLineLength: $maxLineLength,
						defaultCharSet: $this->defaultCharSet
					) . '>' . MailerConstants::CRLF;
			}

			$encodedName = MailerFunctions::encodeHeaderText(
				string: MailerFunctions::secureHeader(string: $fileName),
				maxLineLength: $maxLineLength,
				defaultCharSet: $this->defaultCharSet
			);
			$disposition = $dispositionInline ? 'inline' : 'attachment';
			$mime[] = 'Content-Disposition: ' . $disposition . '; filename=' . $this->quotedString($encodedName) . MailerConstants::CRLF . MailerConstants::CRLF;

			if ($isStringAttachment) {
				$mime[] = MailerFunctions::encodeString(string: $string, encoding: $encoding);
			} else {
				$mime[] = $this->encodeFile(path: $path, encoding: $encoding);
			}
			$mime[] = MailerConstants::CRLF;
		}

		$mime[] = '--' . $boundary . '--' . MailerConstants::CRLF;

		return implode(separator: StringUtils::IMPLODE_DEFAULT_SEPARATOR, array: $mime);
	}

	private function endBoundary(string $boundary): string
	{
		return MailerConstants::CRLF . '--' . $boundary . '--' . MailerConstants::CRLF;
	}

	private function quotedString(string $string): string
	{
		if (preg_match(pattern: '/[ ()<>@,;:"\/\[\]?=]/', subject: $string)) {
			// If the string contains any of these chars, it must be double-quoted and any double quotes must be escaped with a backslash
			return '"' . str_replace(search: '"', replace: '\\"', subject: $string) . '"';
		}

		// Return the string untouched, it doesn't need quoting
		return $string;
	}

	private function encodeFile(string $path, string $encoding): string
	{
		if (!MailerFunctions::fileIsAccessible(path: $path)) {
			throw new MailerException(message: 'File Error: Could not open file: ' . $path);
		}
		$file_buffer = file_get_contents(filename: $path);
		if (false === $file_buffer) {
			throw new MailerException(message: 'File Error: Could not open file: ' . $path);
		}

		return MailerFunctions::encodeString(string: $file_buffer, encoding: $encoding);
	}

	private function inlineAlternativeBody(
		string                     $firstBoundary,
		string                     $secondBoundary,
		string                     $alternativeBody,
		string                     $alternativeBodyCharSet,
		string                     $alternativeBodyEncoding,
		int                        $maxLineLength,
		string                     $bodyCharSet,
		string                     $bodyEncoding,
		string                     $rawBody,
		MailerAttachmentCollection $mailerAttachmentCollection
	): string {
		$string = $this->getBoundary(
			boundary: $firstBoundary,
			charSet: $alternativeBodyCharSet,
			contentType: MailerConstants::CONTENT_TYPE_PLAINTEXT,
			encoding: $alternativeBodyEncoding,
			stringToEncode: $alternativeBody
		);
		$string .= MailerFunctions::textLine(value: '--' . $firstBoundary);
		$string .= MailerHeader::createRaw(name: 'Content-Type', value: MailerConstants::CONTENT_TYPE_MULTIPART_RELATED . ';');
		$string .= MailerFunctions::textLine(value: ' boundary="' . $secondBoundary . '";');
		$string .= MailerFunctions::textLine(value: ' type="' . MailerConstants::CONTENT_TYPE_TEXT_HTML . '"');
		$string .= MailerConstants::CRLF;
		$string .= $this->getBoundary(
			boundary: $secondBoundary,
			charSet: $bodyCharSet,
			contentType: MailerConstants::CONTENT_TYPE_TEXT_HTML,
			encoding: $bodyEncoding,
			stringToEncode: $rawBody
		);
		$string .= $this->attachAll(
			mailerAttachmentCollection: $mailerAttachmentCollection,
			maxLineLength: $maxLineLength,
			dispositionInline: 'inline',
			boundary: $secondBoundary
		);
		$string .= MailerConstants::CRLF;
		$string .= $this->endBoundary(boundary: $firstBoundary);

		return $string;
	}
}