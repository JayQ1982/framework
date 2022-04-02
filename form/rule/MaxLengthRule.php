<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\rule;

use ArrayObject;
use framework\form\component\FormField;
use framework\form\FormRule;
use framework\html\HtmlText;
use UnexpectedValueException;

class MaxLengthRule extends FormRule
{
	protected int $maxLength;

	function __construct(int $maxLength, HtmlText $errorMessage)
	{
		$this->maxLength = $maxLength;

		parent::__construct($errorMessage);
	}

	public function validate(FormField $formField): bool
	{
		if ($formField->isValueEmpty()) {
			return true;
		}

		$fieldValue = $formField->getRawValue();

		if (is_scalar($fieldValue)) {
			return $this->checkValueLengthAgainst(mb_strlen($fieldValue));
		}
		if (is_array($fieldValue) || $fieldValue instanceof ArrayObject) {
			return $this->checkValueLengthAgainst(count($fieldValue));
		}
		throw new UnexpectedValueException('Could not handle field value for rule ' . __CLASS__);
	}

	private function checkValueLengthAgainst($valueLength): bool
	{
		return ($valueLength <= $this->maxLength);
	}
}