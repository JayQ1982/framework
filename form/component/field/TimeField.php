<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\rule\ValidTimeRule;
use framework\html\HtmlText;

class TimeField extends DateTimeFieldCore
{
	protected string $renderValueFormat = 'H:i';

	public function __construct(string $name, HtmlText $label, ?string $value, HtmlText $invalidError, ?HtmlText $requiredError = null)
	{
		parent::__construct($name, $label, $value, $requiredError);
		$this->addRule(new ValidTimeRule($invalidError));
	}
}