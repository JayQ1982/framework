<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\rule\RequiredRule;
use framework\form\settings\AutoCompleteValue;
use framework\form\settings\InputTypeValue;
use framework\html\HtmlText;

class PasswordField extends InputField
{
	public function __construct(
		string             $name,
		HtmlText           $label,
		HtmlText           $requiredError,
		?string            $placeholder = null,
		?AutoCompleteValue $autoComplete = null
	) {
		parent::__construct(
			inputType: InputTypeValue::PASSWORD,
			name: $name,
			label: $label,
			value: '',
			placeholder: $placeholder,
			autoComplete: $autoComplete
		);
		$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredError));
	}
}