<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer;

/**
 * Adapted from https://github.com/PHPMailer/PHPMailer
 */
class MailerConstants
{
	public const CHARSET_ASCII = 'us-ascii';
	public const CHARSET_UTF8 = 'utf-8';
	public const CHARSET_LIST = [
		MailerConstants::CHARSET_ASCII,
		MailerConstants::CHARSET_UTF8,
	];

	public const ENCODING_7BIT = '7bit';
	public const ENCODING_8BIT = '8bit';
	public const ENCODING_BASE64 = 'base64';
	public const ENCODING_BINARY = 'binary';
	public const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
	public const ENCODING_LIST = [
		MailerConstants::ENCODING_7BIT,
		MailerConstants::ENCODING_8BIT,
		MailerConstants::ENCODING_BASE64,
		MailerConstants::ENCODING_BINARY,
		MailerConstants::ENCODING_QUOTED_PRINTABLE,
	];

	public const PRIORITY_HIGH = 1;
	public const PRIORITY_NORMAL = 3;
	public const PRIORITY_LOW = 5;
	public const PRIORITY_LIST = [
		MailerConstants::PRIORITY_HIGH,
		MailerConstants::PRIORITY_NORMAL,
		MailerConstants::PRIORITY_LOW,
	];

	public const CONTENT_TYPE_PLAINTEXT = 'text/plain';
	public const CONTENT_TYPE_TEXT_HTML = 'text/html';
	public const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
	public const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
	public const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';

	public const CRLF = "\r\n";
	public const MAIL_MAX_LINE_LENGTH = 63; // mail() will sometimes corrupt messages with headers longer than 65 chars
	public const MAX_LINE_LENGTH = 998; // The maximum line length allowed by RFC 2822 section 2.1.1.
	/**
	 * The lower maximum line length allowed by RFC 2822 section 2.1.1.
	 * This length does NOT include the line break 76 means that lines will be 77 or 78 chars depending on whether the line break format is LF or CRLF; both
	 * are valid.
	 */
	public const STD_LINE_LENGTH = 76;
}