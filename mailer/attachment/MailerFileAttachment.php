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

namespace framework\mailer\attachment;

use framework\mailer\MailerConstants;
use framework\mailer\MailerException;
use framework\mailer\MailerFunctions;

class MailerFileAttachment
{
	private string $path;
	private string $fileName;
	private string $type;

	public function __construct(
		string         $path,
		string         $fileName = '',
		private string $encoding = MailerConstants::ENCODING_BASE64,
		string         $type = '',
		private bool   $dispositionInline = false
	) {
		if (!in_array(needle: $this->encoding, haystack: MailerConstants::ENCODING_LIST)) {
			throw new MailerException(message: 'Invalid encoding "' . $this->encoding . '". See MailerConstants::ENCODING_LIST[].');
		}

		$path = trim($path);
		if ($path === '') {
			throw new MailerException(message: 'Empty path.');
		}
		if (!MailerFunctions::fileIsAccessible(path: $path)) {
			throw new MailerException(message: 'Could not access file: ' . $path);
		}
		$this->path = $path;

		$fileName = trim($fileName);
		if ($fileName === '') {
			$fileName = MailerFunctions::mb_pathinfo(path: $path, options: PATHINFO_BASENAME);
		}
		$this->fileName = $fileName;

		$type = trim($type);
		if ($type === '') {
			$type = MailerFunctions::filenameToType(fileName: $path);
		}
		$this->type = $type;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getFileName(): string
	{
		return $this->fileName;
	}

	public function getEncoding(): string
	{
		return $this->encoding;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function isDispositionInline(): bool
	{
		return $this->dispositionInline;
	}
}