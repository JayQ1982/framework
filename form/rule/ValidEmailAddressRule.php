<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\rule;

use framework\common\StringUtils;
use framework\common\ValidatedEmailAddress;
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

		$fieldValue = trim($formField->getRawValue());

		$validatedEmailAddress = new ValidatedEmailAddress($fieldValue);
		if (!$validatedEmailAddress->isValidSyntax()) {
			return false;
		}

		$emailParts = explode('@', $fieldValue);
		$domain = StringUtils::utf8_to_punycode($emailParts[1]);
		$formField->setValue($emailParts[0] . '@' . $domain);

		if (!$this->dnsCheck) {
			return true;
		}

		return $validatedEmailAddress->isResolvable($this->trueOnDnsError);
	}
}