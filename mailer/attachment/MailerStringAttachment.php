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

namespace framework\mailer\attachment;

use framework\mailer\MailerConstants;
use framework\mailer\MailerException;
use framework\mailer\MailerFunctions;

readonly class MailerStringAttachment
{
	public string $contentString;
	public string $fileName;
	public string $type;
	public string $encoding;

	public function __construct(
		string      $contentString,
		string      $fileName,
		string      $type,
		public bool $dispositionInline = false
	) {
		$contentString = trim(string: $contentString);
		$fileName = trim(string: $fileName);
		$this->encoding = MailerConstants::ENCODING_BASE64;
		if ($contentString === '' || $fileName === '') {
			throw new MailerException(message: 'Empty contentString or fileName.');
		}
		$type = trim(string: $type);
		if ($type === '') {
			$type = MailerFunctions::filenameToType(fileName: $fileName);
		}
		$this->contentString = $contentString;
		$this->fileName = $fileName;
		$this->type = $type;
	}
}