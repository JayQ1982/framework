<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

class PasswordField extends TextField
{
	protected string $type = 'password';

	public function __construct(string $name, string $label, string $requiredError)
	{
		parent::__construct($name, $label, '', $requiredError);
	}
}
/* EOF */