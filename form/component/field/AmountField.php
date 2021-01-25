<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\component\field;

use framework\form\rule\ValidAmountRule;
use framework\html\HtmlText;

class AmountField extends TextField
{
	public function __construct(string $name, HtmlText $label, bool $valueIsFloat, null|int|float $initialValue = null, ?HtmlText $individualInvalidError = null, ?HtmlText $requiredError = null)
	{
		parent::__construct($name, $label, $initialValue, $requiredError);

		$invalidError = is_null($individualInvalidError) ? new HtmlText('Der angegebene Wert ist ungültig.', true) : $individualInvalidError;
		$this->addRule(new ValidAmountRule($valueIsFloat, $invalidError));
	}
}