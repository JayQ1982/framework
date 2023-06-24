<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\form\renderer\InputFieldRenderer;
use framework\form\settings\AutoCompleteValue;
use framework\form\settings\InputTypeValue;
use framework\html\HtmlText;

abstract class InputField extends FormField
{
	public function __construct(
		public readonly InputTypeValue     $inputType,
		string                             $name,
		HtmlText                           $label,
		int|float|string|bool|null         $value,
		public readonly ?string            $placeholder,
		public readonly ?AutoCompleteValue $autoComplete
	) {
		parent::__construct(
			name: $name,
			label: $label,
			value: $value
		);
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new InputFieldRenderer(formField: $this);
	}
}