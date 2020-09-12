<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormRule;
use UnexpectedValueException;

class MaxValueRule extends FormRule
{
	protected $maxValue;

	/**
	 * @param mixed  $maxValue
	 * @param string $errorMessage
	 */
	function __construct($maxValue, string $errorMessage)
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

		if (is_scalar($fieldValue) === true) {
			return ($fieldValue <= $this->maxValue);
		}

		throw new UnexpectedValueException('Could not handle field value for rule ' . __CLASS__);
	}
}
/* EOF */