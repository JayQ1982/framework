<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form;

use framework\form\component\FormField;

abstract class FormRule
{
	/** @var string $errorMessage : The error message if validation fails */
	protected string $errorMessage;

	/**
	 * @param string $errorMessage : The error message to be set
	 */
	public function __construct(string $errorMessage)
	{
		$this->errorMessage = $errorMessage;
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
	 * @param string $errorMessage : The new error message for this rule
	 */
	public function setErrorMessage(string $errorMessage): void
	{
		$this->errorMessage = $errorMessage;
	}

	/**
	 * Get the error message specified for this rule
	 *
	 * @return string : The error message
	 */
	public function getErrorMessage(): string
	{
		return $this->errorMessage;
	}
}
/* EOF */