<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\mailer;

enum MailerAddressKindEnum: string
{
	case KIND_SENDER = 'Sender';
	case KIND_FROM = 'From';
	case KIND_CONFIRM_READING_TO = 'ConfirmReadingTo';
	case KIND_TO = 'To';
	case KIND_CC = 'Cc';
	case KIND_BCC = 'Bcc';
	case KIND_REPLY_TO = 'Reply-To';
}