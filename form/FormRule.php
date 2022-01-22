<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form;

use framework\form\component\FormField;
use framework\html\HtmlText;

abstract class FormRule
{
	private HtmlText $validationErrorMessage;

	public function __construct(HtmlText $defaultErrorMessage)
	{
		$this->validationErrorMessage = $defaultErrorMessage;
	}

	/**
	 * Method to validate a form field.
	 *
	 * @param FormField $formField The field instance to check against
	 *
	 * @return bool
	 */
	public abstract function validate(FormField $formField): bool;

	/**
	 * Overwrite the error message for this rule.
	 *
	 * @param HtmlText $errorMessage : The new error message for this rule
	 */
	public function setErrorMessage(HtmlText $errorMessage): void
	{
		$this->validationErrorMessage = $errorMessage;
	}

	public function getErrorMessage(): HtmlText
	{
		return $this->validationErrorMessage;
	}
}