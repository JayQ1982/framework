<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormRule;
use UnexpectedValueException;

class MinValueRule extends FormRule
{
	protected $minValue;

	/**
	 * @param mixed  $minValue
	 * @param string $errorMessage
	 */
	function __construct($minValue, string $errorMessage)
	{
		parent::__construct($errorMessage);

		$this->minValue = $minValue;
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		if (is_scalar($fieldValue) === true) {
			return ($fieldValue >= $this->minValue);
		}

		throw new UnexpectedValueException('Could not handle field value for rule ' . __CLASS__);
	}
}
/* EOF */