<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\rule\ValidDateRule;
use framework\html\HtmlText;

class DateField extends DateTimeFieldCore
{
	protected string $renderValueFormat = 'd.m.Y';

	public function __construct(string $name, HtmlText $label, ?string $value, HtmlText $invalidError, ?HtmlText $requiredError = null)
	{
		parent::__construct($name, $label, $value, $requiredError);
		$this->addRule(new ValidDateRule($invalidError));
	}
}