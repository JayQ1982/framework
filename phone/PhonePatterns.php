<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\phone;

/**
 * Adapted from https://github.com/google/libphonenumber
 */
class PhonePatterns
{
	public const VALID_START_CHAR_PATTERN = '[' . PhoneConstants::PLUS_CHARS . PhoneConstants::DIGITS . ']';
	public const UNWANTED_END_CHAR_PATTERN = "[[\\P{N}&&\\P{L}]&&[^#]]+$";
	public const SECOND_NUMBER_START_PATTERN = '[\\\\/] *x';
	public const MIN_LENGTH_PHONE_NUMBER_PATTERN = '[' . PhoneConstants::DIGITS . ']{' . PhoneConstants::MIN_LENGTH_FOR_NSN . '}';
	public const EXTN_PATTERNS_FOR_PARSING = (PhoneConstants::RFC3966_EXTN_PREFIX . PhoneConstants::CAPTURING_EXTN_DIGITS . '|' . "[ \xC2\xA0\\t,]*" .
		"(?:e?xt(?:ensi(?:o\xCC\x81?|\xC3\xB3))?n?|(?:\xEF\xBD\x85)?\xEF\xBD\x98\xEF\xBD\x94(?:\xEF\xBD\x8E)?|" .
		'доб|' . '[' . ',;' . "x\xEF\xBD\x98#\xEF\xBC\x83~\xEF\xBD\x9E" . "]|int|\xEF\xBD\x89\xEF\xBD\x8E\xEF\xBD\x94|anexo)" .
		"[:\\.\xEF\xBC\x8E]?[ \xC2\xA0\\t,-]*" . PhoneConstants::CAPTURING_EXTN_DIGITS . "\\#?|" .
		'[- ]+(' . PhoneConstants::DIGITS . "{1,5})\\#");
	public const VALID_PHONE_NUMBER_PATTERN = '%^' . PhonePatterns::MIN_LENGTH_PHONE_NUMBER_PATTERN . '$|^' . PhoneConstants::VALID_PHONE_NUMBER . '(?:' . PhonePatterns::EXTN_PATTERNS_FOR_PARSING . ')?$%' . PhoneConstants::REGEX_FLAGS;
	public const PLUS_CHARS_PATTERN = '[' . PhoneConstants::PLUS_CHARS . ']+';
	public const VALID_ALPHA_PHONE_PATTERN = '(?:.*?[A-Za-z]){3}.*';
	public const CAPTURING_DIGIT_PATTERN = '(' . PhoneConstants::DIGITS . ')';
}