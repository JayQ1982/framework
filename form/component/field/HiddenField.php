<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\component\field;

use framework\form\renderer\HiddenFieldRenderer;
use framework\html\HtmlText;

class HiddenField extends InputField
{
	protected string $type = 'hidden';

	/**
	 * See comment in FormField->__construct() for a further description of the following parameters.
	 * Because hidden fields don't have a label, we don't ask for it in the constructor and force it to ''
	 * We also force a renderer for this field so it doesn't get wrapped (for example as DefinitionList) in collections.
	 *
	 * @param string $name
	 * @param        $value
	 */
	public function __construct(string $name, $value = null)
	{
		parent::__construct($name, new HtmlText('', true), $value);
		$this->setRenderer(new HiddenFieldRenderer($this));
	}
}