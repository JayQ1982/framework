<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use framework\form\rule\ValidEmailAddressRule;

class EmailField extends TextField
{
	/**
	 * @param string      $name           : Internal FormElement-name
	 * @param string      $label          : Label to be displayed by renderer
	 * @param string|null $value          : Field value
	 * @param string      $invalidError   : Error message to render if ValidEmailAddressRule validation returns false
	 * @param string|null $requiredError  : Error message to render if field is required and no value is given
	 * @param bool        $dnsCheck       : Do additional DNS checks
	 * @param bool        $trueOnDnsError : Return true if dns check fails due to a technical error
	 */
	public function __construct(string $name, string $label, ?string $value, string $invalidError, ?string $requiredError = null, $dnsCheck = true, $trueOnDnsError = true)
	{
		parent::__construct($name, $label, $value, $requiredError);
		$this->addRule(new ValidEmailAddressRule($invalidError, $dnsCheck, $trueOnDnsError));
	}
}
/* EOF */