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

class TextField extends InputField
{
	public function __construct(
		string             $name,
		HtmlText           $label,
		?string            $value = null,
		?HtmlText          $requiredError = null,
		?string            $placeholder = null,
		?AutoCompleteValue $autoComplete = null
	) {
		parent::__construct(
			inputType: InputTypeValue::TEXT,
			name: $name,
			label: $label,
			value: $value,
			placeholder: $placeholder,
			autoComplete: $autoComplete
		);
		if (!is_null(value: $requiredError)) {
			$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredError));
		}
	}
}