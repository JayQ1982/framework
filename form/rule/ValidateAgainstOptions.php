<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormOptions;
use framework\form\FormRule;
use framework\html\HtmlText;
use UnexpectedValueException;

class ValidateAgainstOptions extends FormRule
{
	private FormOptions $validFormOptions;

	function __construct(HtmlText $errorMessage, FormOptions $validFormOptions)
	{
		$this->validFormOptions = $validFormOptions;

		parent::__construct($errorMessage);
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		if (is_scalar($fieldValue)) {
			return $this->validFormOptions->exists($fieldValue);
		}

		if (is_array($fieldValue)) {
			foreach ($fieldValue as $elementValue) {
				if (!is_scalar($elementValue)) {
					return false;
				}

				if (!$this->validFormOptions->exists($elementValue)) {
					return false;
				}
			}

			return true;
		}

		throw new UnexpectedValueException('The field value is neither a scalar data type nor an array');
	}
}