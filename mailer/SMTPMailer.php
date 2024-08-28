<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer;

use framework\common\StringUtils;
use RuntimeException;

class SMTPMailer extends AbstractMailer
{
	/** @var resource $stream */
	private $stream;
	private array $log = [];

	public function __construct(
		private readonly string $hostName,
		private readonly string $smtpUserName,
		private readonly string $smtpPassword,
		private readonly int    $port = 587,
		private readonly bool   $useTls = true
	) {
	}

	public function headerHasTo(): bool
	{
		return true;
	}

	public function headerHasSubject(): bool
	{
		return true;
	}

	public function getMaxLineLength(): int
	{
		return MailerConstants::MAX_LINE_LENGTH;
	}

	public function sendMail(
		AbstractMail   $abstractMail,
		MailMimeHeader $mailMimeHeader,
		MailMimeBody   $mailMimeBody
	): void {
		$this->stream = fsockopen(
			hostname: $this->hostName,
			port: $this->port,
			error_code: $enum,
			error_message: $estr,
			timeout: 30
		);
		if (!$this->stream) {
			throw new RuntimeException(message: 'Socket connection error: ' . $this->hostName);
		}
		$serverName = $this->getServerName();
		$this->response(expectedCode: '220');
		$this->sendCommand(
			command: 'EHLO ' . $serverName,
			expectedCode: 250
		);
		if ($this->useTls) {
			$this->sendCommand(
				command: 'STARTTLS',
				expectedCode: 220
			);
			stream_socket_enable_crypto(
				stream: $this->stream,
				enable: true,
				crypto_method: STREAM_CRYPTO_METHOD_TLS_CLIENT
			);
			$this->sendCommand(
				command: 'EHLO ' . $serverName,
				expectedCode: 250
			);
		}
		if ($this->smtpUserName !== '') {
			$this->sendCommand(
				command: 'AUTH LOGIN',
				expectedCode: 334
			);
			$this->sendCommand(
				command: base64_encode(string: $this->smtpUserName),
				expectedCode: 334
			);
			$this->sendCommand(
				command: base64_encode(string: $this->smtpPassword),
				expectedCode: 235
			);
		}
		$this->sendCommand(
			command: 'MAIL FROM: <' . $abstractMail->sender->getPunyEncodedEmail() . '>',
			expectedCode: 250
		);
		foreach ($abstractMail->mailerAddressCollection->list(mailerAddressKindEnum: MailerAddressKindEnum::KIND_TO) as $mailerAddress) {
			$this->sendCommand(
				command: 'RCPT TO: <' . $mailerAddress->getPunyEncodedEmail() . '>',
				expectedCode: 250
			);
		}
		foreach ($abstractMail->mailerAddressCollection->list(mailerAddressKindEnum: MailerAddressKindEnum::KIND_CC) as $mailerAddress) {
			$this->sendCommand(
				command: 'RCPT TO: <' . $mailerAddress->getPunyEncodedEmail() . '>',
				expectedCode: 250
			);
		}
		foreach ($abstractMail->mailerAddressCollection->list(mailerAddressKindEnum: MailerAddressKindEnum::KIND_BCC) as $mailerAddress) {
			$this->sendCommand(
				command: 'RCPT TO: <' . $mailerAddress->getPunyEncodedEmail() . '>',
				expectedCode: 250
			);
		}
		$this->sendData(
			data: implode(
				separator: StringUtils::IMPLODE_DEFAULT_SEPARATOR,
				array: [
					$mailMimeHeader->getMimeHeader(),
					MailerConstants::CRLF,
					MailerConstants::CRLF,
					$mailMimeBody->getMimeBody(),
				]
			)
		);
		$this->sendCommand(
			command: 'QUIT',
			expectedCode: 221
		);
		$this->close();
	}

	private function sendCommand(string $command, int $expectedCode): void
	{
		$this->sendRawDataToServer(data: $command);
		$this->response(expectedCode: $expectedCode);
	}

	private function sendRawDataToServer(string $data): void
	{
		$this->log[] = htmlspecialchars(string: $data);
		fwrite(stream: $this->stream, data: $data . MailerConstants::CRLF);
	}

	private function response(int $expectedCode): void
	{
		stream_set_timeout(stream: $this->stream, seconds: 8);
		$result = fread(stream: $this->stream, length: 768);
		$meta = stream_get_meta_data(stream: $this->stream);
		if ($meta['timed_out'] === true) {
			fclose(stream: $this->stream);
			throw new RuntimeException(message: 'Server timeout');
		}
		$this->log[] = $result;
		$responseCode = (int)substr(string: $result, offset: 0, length: 3);
		if ($responseCode === $expectedCode) {
			return;
		}
		fclose(stream: $this->stream);
		throw new RuntimeException(message: 'Unexpected server response code ' . $responseCode);
	}

	private function close(): void
	{
		if (is_resource(value: $this->stream)) {
			fclose(stream: $this->stream);
			$this->stream = null;
		}
	}

	public function __destruct()
	{
		$this->close();
	}

	public function getLog(): array
	{
		return $this->log;
	}

	/**
	 * Send an SMTP DATA command.
	 * Issues a data command and sends the msg_data to the server,
	 * finalizing the mail transaction. $msg_data is the message
	 * that is to be sent with the headers. Each header needs to be
	 * on a single line followed by a <CRLF> with the message headers
	 * and the message body being separated by an additional <CRLF>.
	 * Implements RFC 821: DATA <CRLF>.
	 *
	 * @param string $data Message data to send
	 */
	public function sendData(string $data): void
	{
		$this->sendCommand(
			command: 'DATA',
			expectedCode: 354
		);
		/**
		 * The server is ready to accept data!
		 * According to rfc821 we should not send more than 1000 characters on a single line (including the LE)
		 * so we will break the data up into lines by \r and/or \n then if needed we will break each of those into
		 * smaller lines to fit within the limit.
		 * We will also look for lines that start with a '.' and prepend an additional '.'.
		 * NOTE: this does not count towards line-length limit.
		 */

		// Normalize line breaks before exploding
		$lines = explode(
			separator: "\n",
			string: str_replace(
				search: [
					"\r\n",
					"\r",
				],
				replace: "\n",
				subject: $data)
		);

		/**
		 * To distinguish between a complete RFC822 message and a plain message body, we check if the first field
		 * of the first line (':' separated) does not contain a space then it _should_ be a header, and we will
		 * process all lines before a blank line as headers.
		 */
		$field = substr(
			string: $lines[0],
			offset: 0,
			length: strpos(
				haystack: $lines[0],
				needle: ':'
			)
		);
		$in_headers = false;
		if (
			$field != ''
			&& !str_contains(haystack: $field, needle: ' ')
		) {
			$in_headers = true;
		}
		foreach ($lines as $line) {
			$lines_out = [];
			if ($in_headers && $line === '') {
				$in_headers = false;
			}
			// Break this line up into several smaller lines if it's too long
			while (strlen(string: $line) > MailerConstants::MAX_LINE_LENGTH) {
				// Working backwards, try to find a space within the last MAX_LINE_LENGTH chars of the line to break on
				// so to avoid breaking in the middle of a word
				$pos = strrpos(
					haystack: substr(
						string: $line,
						offset: 0,
						length: MailerConstants::MAX_LINE_LENGTH
					),
					needle: ' '
				);
				if ($pos === false || $pos === 0) {
					// No nice break found, add a hard break
					$pos = MailerConstants::MAX_LINE_LENGTH - 1;
					$lines_out[] = substr(string: $line, offset: 0, length: $pos);
					$line = substr(string: $line, offset: $pos);
				} else {
					// Break at the found point
					$lines_out[] = substr(string: $line, offset: 0, length: $pos);
					// Move along by the amount we dealt with
					$line = substr(string: $line, offset: $pos + 1);
				}
				// If processing headers add a LWSP-char to the front of new line RFC822 section 3.1.1
				if ($in_headers) {
					$line = "\t" . $line;
				}
			}
			$lines_out[] = $line;

			// Send the lines to the server
			foreach ($lines_out as $line_out) {
				// Dot-stuffing as per RFC5321 section 4.5.2
				// https://tools.ietf.org/html/rfc5321#section-4.5.2
				if (str_starts_with(haystack: $line, needle: '.')) {
					$line_out = '.' . $line_out;
				}
				$this->sendRawDataToServer(data: $line_out);
			}
		}
		// Message data has been sent, complete the command
		$this->sendCommand(command: '.', expectedCode: 250); // DATA END
	}
}