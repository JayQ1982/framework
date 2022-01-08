<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\rule\RequiredRule;
use framework\html\HtmlText;

class TextField extends InputField
{
	protected string $type = 'text';

	/**
	 * See comment in FormField->__construct() for a further description of the following parameters.
	 * We overwrite the constructor to add a "RequiredRule" if $requiredError is set (as string).
	 *
	 * @param string        $name
	 * @param HtmlText      $label
	 * @param null|string   $value
	 * @param null|HtmlText $requiredError : If not null we add a RequiredRule with the $requiredError as message
	 */
	public function __construct(string $name, HtmlText $label, ?string $value = null, ?HtmlText $requiredError = null)
	{
		parent::__construct($name, $label, $value);

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}
	}
}