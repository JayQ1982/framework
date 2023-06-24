<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\rule\ValidTimeRule;
use framework\form\settings\AutoCompleteValue;
use framework\html\HtmlText;

class TimeField extends DateTimeFieldCore
{
	public function __construct(
		string             $name,
		HtmlText           $label,
		?string            $value,
		HtmlText           $invalidError,
		?HtmlText          $requiredError = null,
		?string            $placeholder = null,
		?AutoCompleteValue $autoComplete = null
	) {
		parent::__construct(
			name: $name,
			label: $label,
			value: $value,
			requiredError: $requiredError,
			placeholder: $placeholder,
			autoComplete: $autoComplete
		);
		$this->setRenderValueFormat(renderValueFormat: 'H:i');
		$this->addRule(formRule: new ValidTimeRule(defaultErrorMessage: $invalidError));
	}
}