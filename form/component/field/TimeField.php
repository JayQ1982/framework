<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use framework\form\rule\ValidTimeRule;

class TimeField extends DateTimeFieldCore
{
	protected string $renderValueFormat = 'H:i';

	public function __construct(string $name, string $label, ?string $value, string $invalidError, ?string $requiredError = null)
	{
		parent::__construct($name, $label, $value, $requiredError);
		$this->addRule(new ValidTimeRule($invalidError));
	}
}
/* EOF */