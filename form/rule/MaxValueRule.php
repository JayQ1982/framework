<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormRule;
use framework\html\HtmlText;
use UnexpectedValueException;

class MaxValueRule extends FormRule
{
	protected float|int $maxValue;

	function __construct(float|int $maxValue, HtmlText $errorMessage)
	{
		parent::__construct($errorMessage);

		$this->maxValue = $maxValue;
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		if (is_int($fieldValue) || is_float($fieldValue)) {
			return ($fieldValue <= $this->maxValue);
		}

		throw new UnexpectedValueException('Could not handle field value for rule ' . __CLASS__);
	}
}