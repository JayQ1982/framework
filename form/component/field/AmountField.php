<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use framework\form\rule\ValidAmountRule;

class AmountField extends TextField
{
	/**
	 * @param string      $name          : See FormField->__construct()
	 * @param string      $label         : See FormField->__construct()
	 * @param bool        $float         : true if we expect value to be float, false we just expect an integer
	 * @param null        $value         : See FormField->__construct()
	 * @param string      $invalidError  : The error message to be displayed if value is invalid
	 * @param null|string $requiredError : See TextField->__construct()
	 */
	public function __construct(string $name, string $label, bool $float, $value = null, string $invalidError = 'Der angegebene Wert ist ungültig.', ?string $requiredError = null)
	{
		parent::__construct($name, $label, $value, $requiredError);
		$this->addRule(new ValidAmountRule($float, $invalidError));
	}
}
/* EOF */