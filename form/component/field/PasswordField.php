<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\component\field;

use framework\html\HtmlText;

class PasswordField extends TextField
{
	protected string $type = 'password';

	public function __construct(string $name, HtmlText $label, HtmlText $requiredError)
	{
		parent::__construct($name, $label, '', $requiredError);
	}
}