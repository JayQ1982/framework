<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\rule\RequiredRule;
use framework\form\rule\ValidEmailAddressRule;
use framework\form\settings\AutoCompleteValue;
use framework\form\settings\InputTypeValue;
use framework\html\HtmlText;

class EmailField extends InputField
{
	public function __construct(
		string             $name,
		HtmlText           $label,
		?string            $value,
		HtmlText           $invalidError,
		?HtmlText          $requiredError = null,
		bool               $dnsCheck = true,
		bool               $trueOnDnsError = true,
		?string            $placeholder = null,
		?AutoCompleteValue $autoComplete = null
	) {
		parent::__construct(
			inputType: InputTypeValue::EMAIL,
			name: $name,
			label: $label,
			value: $value,
			placeholder: $placeholder,
			autoComplete: $autoComplete
		);
		if (!is_null(value: $requiredError)) {
			$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredError));
		}
		$this->addRule(formRule: new ValidEmailAddressRule(errorMessage: $invalidError, dnsCheck: $dnsCheck, trueOnDnsError: $trueOnDnsError));
	}
}