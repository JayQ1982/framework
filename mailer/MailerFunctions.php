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

class MailerFunctions
{
	public static function stripTrailingWSP(string $text): string
	{
		return rtrim(string: $text, characters: " \r\n\t");
	}

	public static function validateAddress(string $address): bool
	{
		// Reject line breaks in addresses; it's valid RFC5322, but not RFC5321
		if (str_contains(haystack: $address, needle: "\n") || str_contains(haystack: $address, needle: "\r")) {
			return false;
		}

		return (bool)filter_var(value: $address, filter: FILTER_VALIDATE_EMAIL, options: FILTER_FLAG_EMAIL_UNICODE) !== false;
	}

	public static function has8bitChars(string $text): bool
	{
		$match = preg_match(pattern: '/[\x80-\xFF]/', subject: $text);
		if ($match === false) {
			throw new MailerException(message: 'Failed to check for 8bit characters: ' . $text);
		}

		return ($match === 1);
	}

	public static function punyEncodeDomain(string $domain): string
	{
		return !MailerFunctions::has8bitChars(text: $domain) ? $domain : idn_to_ascii(
			domain: $domain,
			flags: IDNA_DEFAULT | IDNA_USE_STD3_RULES | IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ | IDNA_NONTRANSITIONAL_TO_ASCII,
			variant: INTL_IDNA_VARIANT_UTS46
		);
	}

	public static function textLine(string $value): string
	{
		return $value . MailerConstants::CRLF;
	}

	public static function headerLine(string $name, string $value): string
	{
		return $name . ': ' . $value . MailerConstants::CRLF;
	}

	public static function encodeString(string $string, string $encoding): string
	{
		switch (strtolower($encoding)) {
			case MailerConstants::ENCODING_BASE64:
				return chunk_split(
					string: base64_encode(string: $string),
					length: MailerConstants::STD_LINE_LENGTH,
					separator: MailerConstants::CRLF
				);
			case MailerConstants::ENCODING_7BIT:
			case MailerConstants::ENCODING_8BIT:
				$encoded = MailerFunctions::normalizeBreaks(text: $string);
				// Make sure it ends with a line break
				if (substr(string: $encoded, offset: -(strlen(string: MailerConstants::CRLF))) !== MailerConstants::CRLF) {
					$encoded .= MailerConstants::CRLF;
				}

				return $encoded;
			case MailerConstants::ENCODING_BINARY:
				return $string;
			case MailerConstants::ENCODING_QUOTED_PRINTABLE:
				return MailerFunctions::encodeQP(string: $string);
			default:
				throw new MailerException(message: 'Unknown encoding: ' . $encoding);
		}
	}

	private static function normalizeBreaks(string $text): string
	{
		$breaktype = MailerConstants::CRLF;
		// Normalise to \n
		$text = str_replace(search: [MailerConstants::CRLF, "\r"], replace: "\n", subject: $text);

		// Now convert LE as needed
		return str_replace(search: "\n", replace: $breaktype, subject: $text);
	}

	private static function encodeQP(string $string): string
	{
		return MailerFunctions::normalizeBreaks(text: quoted_printable_encode(string: $string));
	}

	public static function encodeHeaderPhrase(string $string, string $defaultCharSet): string
	{
		if (!preg_match(pattern: '/[\200-\377]/', subject: $string)) {
			//Can't use addslashes as we don't know the value of magic_quotes_sybase
			$encoded = addcslashes(string: $string, characters: "\0..\37\177\\\"");
			if (
				($string === $encoded)
				&& preg_match(pattern: '/[^A-Za-z0-9!#$%&\'*+\/=?^_`{|}~ -]/', subject: $string) !== 1
			) {
				return $encoded;
			}

			return "\"$encoded\"";
		}

		return MailerFunctions::encodeHeader(
			string: $string,
			matchCount: preg_match_all(pattern: '/[^\040\041\043-\133\135-\176]/', subject: $string),
			defaultCharSet: $defaultCharSet,
			isPhrase: true
		);
	}

	public static function encodeHeaderText(string $string, string $defaultCharSet): string
	{
		return MailerFunctions::encodeHeader(
			string: $string,
			matchCount: preg_match_all(pattern: '/[\000-\010\013\014\016-\037\177-\377]/', subject: $string),
			defaultCharSet: $defaultCharSet,
			isPhrase: false
		);
	}

	private static function encodeHeader(string $string, int $matchCount, string $defaultCharSet, bool $isPhrase): string
	{
		if (MailerFunctions::has8bitChars(text: $string)) {
			$charset = $defaultCharSet;
		} else {
			$charset = MailerConstants::CHARSET_ASCII;
		}

		// Q/B encoding adds 8 chars and the charset ("` =?<charset>?[QB]?<content>?=`").
		$overhead = 8 + strlen(string: $charset);
		$maxLength = MailerConstants::MAIL_MAX_LINE_LENGTH - $overhead;

		// Select the encoding that produces the shortest output and/or prevents corruption.
		if ($matchCount > strlen(string: $string) / 3) {
			// More than 1/3 of the content needs encoding, use B-encode.
			$encoding = 'B';
		} else if ($matchCount > 0) {
			// Less than 1/3 of the content needs encoding, use Q-encode.
			$encoding = 'Q';
		} else if (strlen(string: $string) > $maxLength) {
			//No encoding needed, but value exceeds max line length, use Q-encode to prevent corruption.
			$encoding = 'Q';
		} else {
			//No reformatting needed
			$encoding = false;
		}

		switch ($encoding) {
			case 'B':
				if (MailerFunctions::hasMultiBytes(str: $string, charset: $charset)) {
					// Use a custom function which correctly encodes and wraps long multibyte strings without breaking lines within a character
					$encoded = MailerFunctions::base64EncodeWrapMB(str: $string, charset: $charset);
				} else {
					$encoded = base64_encode(string: $string);
					$maxLength -= $maxLength % 4;
					$encoded = trim(string: chunk_split(string: $encoded, length: $maxLength));
				}
				$encoded = preg_replace(
					pattern: '/^(.*)$/m',
					replacement: ' =?' . $charset . "?$encoding?\\1?=",
					subject: $encoded
				);
				break;
			case 'Q':
				$encoded = MailerFunctions::encodeQ(string: $string, isPhrase: $isPhrase);
				$encoded = MailerFunctions::wrapText(message: $encoded, length: $maxLength, charSet: $defaultCharSet, qp_mode: true);
				$encoded = str_replace(
					search: '=' . MailerConstants::CRLF,
					replace: "\n",
					subject: trim(string: $encoded)
				);
				$encoded = preg_replace(
					pattern: '/^(.*)$/m',
					replacement: ' =?' . $charset . "?$encoding?\\1?=",
					subject: $encoded
				);
				break;
			default:
				return $string;
		}

		return trim(string: MailerFunctions::normalizeBreaks(text: $encoded));
	}

	private static function hasMultiBytes(string $str, string $charset): bool
	{
		return strlen(string: $str) > mb_strlen(string: $str, encoding: $charset);
	}

	private static function base64EncodeWrapMB(string $str, string $charset): string
	{
		$linebreak = "\n";
		$start = '=?' . $charset . '?B?';
		$end = '?=';
		$encoded = '';

		$mb_length = mb_strlen(string: $str, encoding: $charset);

		// Each line must have length <= 75, including $start and $end
		$length = 75 - strlen(string: $start) - strlen(string: $end);
		// Average multi-byte ratio
		$ratio = $mb_length / strlen(string: $str);
		// Base64 has a 4:3 ratio
		$avgLength = floor(num: $length * $ratio * .75);

		for ($i = 0; $i < $mb_length; $i += $offset) {
			$lookBack = 0;
			do {
				$offset = $avgLength - $lookBack;
				$chunk = mb_substr(string: $str, start: $i, length: $offset, encoding: $charset);
				$chunk = base64_encode(string: $chunk);
				++$lookBack;
			} while (strlen(string: $chunk) > $length);
			$encoded .= $chunk . $linebreak;
		}

		// Chomp the last linefeed
		return substr(string: $encoded, offset: 0, length: -strlen(string: $linebreak));
	}

	private static function encodeQ(string $string, bool $isPhrase): string
	{
		// There should not be any EOL in the string
		$encoded = str_replace(search: ["\r", "\n"], replace: '', subject: $string);
		$pattern = $isPhrase ? '^A-Za-z0-9!*+\/ -' : '\000-\011\013\014\016-\037\075\077\137\177-\377';
		$matches = [];
		if (preg_match_all(
			pattern: "/[$pattern]/",
			subject: $encoded,
			matches: $matches
		)) {
			// If the string contains an '=', make sure it's the first thing we replace so as to avoid double-encoding
			$eqkey = array_search(needle: '=', haystack: $matches[0], strict: true);
			if (false !== $eqkey) {
				unset($matches[0][$eqkey]);
				array_unshift($matches[0], '=');
			}
			foreach (array_unique(array: $matches[0]) as $char) {
				$encoded = str_replace(search: $char, replace: '=' . sprintf('%02X', ord($char)), subject: $encoded);
			}
		}
		// Replace spaces with _ (more readable than =20)
		// RFC 2047 section 4.2(2)
		return str_replace(search: ' ', replace: '_', subject: $encoded);
	}

	public static function wrapText(string $message, int $length, string $charSet, bool $qp_mode): string
	{
		if ($qp_mode) {
			$soft_break = ' =' . MailerConstants::CRLF;
		} else {
			$soft_break = MailerConstants::CRLF;
		}
		// If utf-8 encoding is used, we will need to make sure we don't split multibyte characters when we wrap
		$is_utf8 = MailerConstants::CHARSET_UTF8 === strtolower($charSet);
		$lelen = strlen(string: MailerConstants::CRLF);
		$crlflen = strlen(string: MailerConstants::CRLF);

		$message = MailerFunctions::normalizeBreaks(text: $message);
		// Remove a trailing line break
		if (substr(string: $message, offset: -$lelen) === MailerConstants::CRLF) {
			$message = substr(string: $message, offset: 0, length: -$lelen);
		}

		// Split message into lines
		$lines = explode(separator: MailerConstants::CRLF, string: $message);

		// Message will be rebuilt in here
		$message = '';
		foreach ($lines as $line) {
			$words = explode(separator: ' ', string: $line);
			$buf = '';
			$firstWord = true;
			foreach ($words as $word) {
				if ($qp_mode && (strlen(string: $word) > $length)) {
					$space_left = $length - strlen(string: $buf) - $crlflen;
					if (!$firstWord) {
						if ($space_left > 20) {
							$len = MailerFunctions::calcLen(len: $space_left, is_utf8: $is_utf8, word: $word);
							$part = substr(string: $word, offset: 0, length: $len);
							$word = substr(string: $word, offset: $len);
							$buf .= ' ' . $part;
							$message .= $buf . '=' . MailerConstants::CRLF;
						} else {
							$message .= $buf . $soft_break;
						}
						$buf = '';
					}
					while ($word !== '') {
						if ($length <= 0) {
							break;
						}
						$len = MailerFunctions::calcLen(len: $length, is_utf8: $is_utf8, word: $word);
						$part = substr(string: $word, offset: 0, length: $len);
						$word = substr(string: $word, offset: $len);

						if ($word !== '') {
							$message .= $part . '=' . MailerConstants::CRLF;
						} else {
							$buf = $part;
						}
					}
				} else {
					$buf_o = $buf;
					if (!$firstWord) {
						$buf .= ' ';
					}
					$buf .= $word;

					if ('' !== $buf_o && strlen(string: $buf) > $length) {
						$message .= $buf_o . $soft_break;
						$buf = $word;
					}
				}
				$firstWord = false;
			}
			$message .= $buf . MailerConstants::CRLF;
		}

		return $message;
	}

	private static function calcLen(int $len, bool $is_utf8, string $word): int
	{
		if ($is_utf8) {
			return MailerFunctions::utf8CharBoundary(encodedText: $word, maxLength: $len);
		}
		if ('=' === substr(string: $word, offset: $len - 1, length: 1)) {
			return --$len;
		}
		if ('=' === substr(string: $word, offset: $len - 2, length: 1)) {
			$len -= 2;

			return $len;
		}

		return $len;
	}

	private static function utf8CharBoundary(string $encodedText, int $maxLength): int
	{
		$foundSplitPos = false;
		$lookBack = 3;
		while (!$foundSplitPos) {
			$lastChunk = substr(string: $encodedText, offset: $maxLength - $lookBack, length: $lookBack);
			$encodedCharPos = strpos(haystack: $lastChunk, needle: '=');
			if (false !== $encodedCharPos) {
				// Found start of encoded character byte within $lookBack block.
				// Check the encoded byte value (the 2 chars after the '=')
				$hex = substr(string: $encodedText, offset: $maxLength - $lookBack + $encodedCharPos + 1, length: 2);
				$dec = hexdec(hex_string: $hex);
				if ($dec < 128) {
					// Single byte character.
					// If the encoded char was found at pos 0, it will fit otherwise reduce maxLength to start of the encoded char
					if ($encodedCharPos > 0) {
						$maxLength -= $lookBack - $encodedCharPos;
					}
					$foundSplitPos = true;
				} else if ($dec >= 192) {
					// First byte of a multibyte character
					// Reduce maxLength to split at start of character
					$maxLength -= $lookBack - $encodedCharPos;
					$foundSplitPos = true;
				} else {
					//Middle byte of a multibyte character, look further back
					$lookBack += 3;
				}
			} else {
				// No encoded character found
				$foundSplitPos = true;
			}
		}

		return $maxLength;
	}

	public static function secureHeader(string $string): string
	{
		return trim(string: str_replace(search: ["\r", "\n"], replace: '', subject: $string));
	}

	public static function fileIsAccessible(string $path): bool
	{
		if (!MailerFunctions::isPermittedPath(path: $path)) {
			return false;
		}
		$readable = file_exists(filename: $path);
		// If not a UNC path (expected to start with \\), check read permission, see #2069
		if (!str_starts_with(haystack: $path, needle: '\\\\')) {
			$readable = $readable && is_readable(filename: $path);
		}

		return $readable;
	}

	private static function isPermittedPath(string $path): bool
	{
		// Matches scheme definition from https://tools.ietf.org/html/rfc3986#section-3.1
		return !preg_match(pattern: '#^[a-z][a-z\d+.-]*://#i', subject: $path);
	}

	public static function isShellSafe(string $string): bool
	{
		if (
			escapeshellcmd(command: $string) !== $string
			|| !in_array(escapeshellarg(arg: $string), ["'$string'", "\"$string\""])
		) {
			return false;
		}

		$length = strlen(string: $string);

		for ($i = 0; $i < $length; ++$i) {
			$c = $string[$i];

			// All other characters have a special meaning in at least one common shell, including = and +.
			// Full stop (.) has a special meaning in cmd.exe, but its impact should be negligible here.
			// Note that this does permit non-Latin alphanumeric characters based on the current locale.
			if (!ctype_alnum(text: $c) && !str_contains(haystack: '@_-.', needle: $c)) {
				return false;
			}
		}

		return true;
	}

	public static function mb_pathinfo(string $path, int $options): string|array
	{
		$ret = ['dirname' => '', 'basename' => '', 'extension' => '', 'filename' => ''];
		$pathInfo = [];
		if (preg_match(
			pattern: '#^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^.\\\\/]+?)|))[\\\\/.]*$#m',
			subject: $path,
			matches: $pathInfo
		)) {
			if (array_key_exists(key: 1, array: $pathInfo)) {
				$ret['dirname'] = $pathInfo[1];
			}
			if (array_key_exists(key: 2, array: $pathInfo)) {
				$ret['basename'] = $pathInfo[2];
			}
			if (array_key_exists(key: 5, array: $pathInfo)) {
				$ret['extension'] = $pathInfo[5];
			}
			if (array_key_exists(key: 3, array: $pathInfo)) {
				$ret['filename'] = $pathInfo[3];
			}
		}

		return match ($options) {
			PATHINFO_DIRNAME => $ret['dirname'],
			PATHINFO_BASENAME => $ret['basename'],
			PATHINFO_EXTENSION => $ret['extension'],
			PATHINFO_FILENAME => $ret['filename'],
			default => throw new MailerException(message: 'Invalid $options value: ' . $options),
		};
	}

	public static function filenameToType(string $fileName): string
	{
		// In case the path is a URL, strip any query string before getting extension
		$qpos = strpos($fileName, '?');
		if (false !== $qpos) {
			$fileName = substr($fileName, 0, $qpos);
		}
		$extension = MailerFunctions::mb_pathinfo(path: $fileName, options: PATHINFO_EXTENSION);

		return MailerMimeTypes::getByExtension(extension: $extension);
	}
}