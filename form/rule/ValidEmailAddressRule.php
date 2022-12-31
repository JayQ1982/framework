<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\rule;

use framework\common\ValidatedEmailAddress;
use framework\datacheck\Sanitizer;
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

		$fieldValue = Sanitizer::trimmedString($formField->getRawValue());

		$validatedEmailAddress = new ValidatedEmailAddress($fieldValue);
		if (!$validatedEmailAddress->isValidSyntax()) {
			return false;
		}

		$emailParts = explode('@', $fieldValue);
		$domain = idn_to_ascii(domain: $emailParts[1]);
		$formField->setValue($emailParts[0] . '@' . $domain);

		if (!$this->dnsCheck) {
			return true;
		}

		return $validatedEmailAddress->isResolvable($this->trueOnDnsError);
	}
}