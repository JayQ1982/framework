<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\component\field;

use LogicException;
use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\form\renderer\InputFieldRenderer;
use framework\html\HtmlText;

abstract class InputField extends FormField
{
	protected string $type;
	private ?string $placeholder = null;

	/**
	 * See comment in FormField->__construct() for a further description of the following parameters.
	 * The only reason why we overwrite the constructor here is to check if is a scalar value (or null).
	 *
	 * @param string                     $name
	 * @param HtmlText                   $label
	 * @param string|float|int|bool|null $value
	 */
	public function __construct(string $name, HtmlText $label, $value = null)
	{
		if (!is_null($value) && !is_scalar($value)) {
			throw new LogicException('InputField-class expects $value to be either scalar or null');
		}

		parent::__construct($name, $label, $value);
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setPlaceholder(string $placeholder): void
	{
		$this->placeholder = $placeholder;
	}

	public function getPlaceholder(): ?string
	{
		return $this->placeholder;
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new InputFieldRenderer($this);
	}
}