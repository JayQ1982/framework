<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\rule\ValidAmountRule;
use framework\form\settings\AutoCompleteValue;
use framework\html\HtmlText;

class AmountField extends TextField
{
	public function __construct(
		string             $name,
		HtmlText           $label,
		bool               $valueIsFloat,
		null|int|float     $initialValue = null,
		?HtmlText          $individualInvalidError = null,
		?HtmlText          $requiredError = null,
		?string            $placeholder = null,
		?AutoCompleteValue $autoComplete = null
	) {
		parent::__construct(
			name: $name,
			label: $label,
			value: $initialValue,
			requiredError: $requiredError,
			placeholder: $placeholder,
			autoComplete: $autoComplete
		);
		$this->addRule(formRule: new ValidAmountRule(
			valueIsFloat: $valueIsFloat,
			errorMessage: is_null(value: $individualInvalidError) ? HtmlText::encoded(textContent: 'Der angegebene Wert ist ungültig.') : $individualInvalidError
		));
	}
}