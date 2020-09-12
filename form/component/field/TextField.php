<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use framework\form\rule\RequiredRule;

class TextField extends InputField
{
	protected string $type = 'text';

	/**
	 * See comment in FormField->__construct() for a further description of the following parameters.
	 * We overwrite the constructor to add a "RequiredRule" if $requiredError is set (as string).
	 *
	 * @param string      $name
	 * @param string      $label
	 * @param null        $value
	 * @param null|string $requiredError : If string we add a RequiredRule with the $requiredError as message
	 */
	public function __construct(string $name, string $label, $value = null, ?string $requiredError = null)
	{
		parent::__construct($name, $label, $value);

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}
	}
}
/* EOF */