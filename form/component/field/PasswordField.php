<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
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