<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\rule\ValidAmountRule;
use framework\html\HtmlText;

class AmountField extends TextField
{
	public function __construct(
		string         $name,
		HtmlText       $label,
		bool           $valueIsFloat,
		null|int|float $initialValue = null,
		?HtmlText      $individualInvalidError = null,
		?HtmlText      $requiredError = null
	) {
		parent::__construct(name: $name, label: $label, value: $initialValue, requiredError: $requiredError);

		$this->addRule(new ValidAmountRule(
			valueIsFloat: $valueIsFloat,
			errorMessage: is_null($individualInvalidError) ? HtmlText::encoded('Der angegebene Wert ist ungültig.') : $individualInvalidError
		));
	}
}