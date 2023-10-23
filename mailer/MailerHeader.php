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

readonly class MailerHeader
{
	private string $name;
	private string $value;

	private function __construct(
		string $name,
		string $value
	) {
		$name = trim(string: $name);
		$value = trim(string: $value);

		// Ensure name is not empty, and that neither name nor value contain line breaks
		if ($name === '' || strpbrk(string: $name . $value, characters: MailerConstants::CRLF) !== false) {
			throw new MailerException(message: 'Invalid header name or value');
		}
		$this->name = $name;
		$this->value = $value;
	}

	public static function createRaw(
		string $name,
		string $value
	): string {
		return (new MailerHeader(
			name: $name,
			value: $value
		))->get();
	}

	public static function createEncodedHeaderText(
		string $name,
		string $value,
		int    $maxLineLength,
		string $defaultCharSet
	): MailerHeader {
		return (new MailerHeader(
			name: $name,
			value: MailerFunctions::encodeHeaderText(
				string: $value,
				maxLineLength: $maxLineLength,
				defaultCharSet: $defaultCharSet
			)
		));
	}

	public function get(): string
	{
		return $this->name . ': ' . $this->value . MailerConstants::CRLF;
	}
}