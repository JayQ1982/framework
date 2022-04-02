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

namespace framework\mailer;

class HtmlMail extends AbstractMail
{
	public function __construct(
		string $senderEmail,
		string $fromEmail,
		string $fromName,
		string $toEmail,
		string $toName,
		string $subject,
		string $htmlBody,
		string $alternativeBody,
		string $charSet = MailerConstants::CHARSET_UTF8,
		string $encoding = MailerConstants::ENCODING_QUOTED_PRINTABLE,
		int    $priority = MailerConstants::PRIORITY_NORMAL
	) {
		parent::__construct(
			senderEmail: $senderEmail,
			fromEmail: $fromEmail,
			fromName: $fromName,
			toEmail: $toEmail,
			toName: $toName,
			subject: $subject,
			charSet: $charSet,
			encoding: $encoding,
			priority: $priority
		);
		$this->setHtmlBody(htmlBody: $htmlBody, alternativeBody: $alternativeBody);
	}
}