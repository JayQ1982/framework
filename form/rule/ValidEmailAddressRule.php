<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\formHandler\rule;

namespace framework\form\rule;

use framework\common\StringUtils;
use framework\form\component\FormField;
use framework\form\FormRule;
use framework\html\HtmlText;

class ValidEmailAddressRule extends FormRule
{
	private bool $dnsCheck;
	private bool $trueOnDnsError;

	function __construct(HtmlText $errorMessage, bool $dnsCheck = true, bool $trueOnDnsError = true)
	{
		$this->dnsCheck = $dnsCheck;
		$this->trueOnDnsError = $trueOnDnsError;

		parent::__construct($errorMessage);
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		$hiddenCharactersToRemoveSilently = [
			' ',
			"\t",
			"\n",
			"\r",
			"&#8203;", "\xE2\x80\x8C", "\xE2\x80\x8B", // https://stackoverflow.com/questions/22600235/remove-unicode-zero-width-space-php
		];
		$sanitizedValue = str_replace($hiddenCharactersToRemoveSilently, '', trim($fieldValue));

		$forbiddenCharacters = [
			',',
			';',
			':', // Catches mailto:
		];
		if ($sanitizedValue !== str_replace($forbiddenCharacters, '', $sanitizedValue)) {
			return false;
		}

		$emailParts = explode('@', $sanitizedValue);

		if (!isset($emailParts[1])) {
			return false;
		}

		// As strange as it seems, the local part is allowed to have '@'!
		// Therefore we must look for the last index to get the domain part:
		$domain = StringUtils::utf8_to_punycode(array_pop($emailParts));

		if ($domain === false) {
			return false;
		}

		// We do NOT verify the name part, as this is too complex.
		// Internal PHP filter does not let pass unicode name part, and
		// external found abhorrent regex patterns
		// promising that do also not work properly.
		if (!filter_var('abc@' . $domain, FILTER_VALIDATE_EMAIL)) {
			return false;
		}

		$email = implode('@', $emailParts) . '@' . $domain;

		$formField->setValue($email);

		if (!$this->dnsCheck) {
			return true;
		}

		$mxRecords = [];
		if (getmxrr($domain, $mxRecords)) {
			return true;
		}

		// Port 25 fallback check if there's no MX record
		$aRecords = @dns_get_record($domain, DNS_A);

		if ($aRecords === false) {
			return $this->trueOnDnsError;
		}

		if (count($aRecords) <= 0) {
			return false;
		}

		$connection = @fsockopen($aRecords[0]['ip'], 25);

		if (is_resource($connection) === true) {
			fclose($connection);

			return true;
		}

		return false;
	}
}