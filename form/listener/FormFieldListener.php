<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\listener;

use framework\form\component\collection\Form;
use framework\form\component\FormField;

abstract class FormFieldListener
{
	/**
	 * @param Form      $form
	 * @param FormField $formField
	 */
	public function onEmptyValueBeforeValidation(Form $form, FormField $formField)
	{

	}

	/**
	 * @param Form      $form
	 * @param FormField $formField
	 */
	public function onEmptyValueAfterValidation(Form $form, FormField $formField)
	{

	}

	/**
	 * @param Form      $form
	 * @param FormField $formField
	 */
	public function onNotEmptyValueBeforeValidation(Form $form, FormField $formField)
	{

	}

	/**
	 * @param Form      $form
	 * @param FormField $formField
	 */
	public function onNotEmptyValueAfterValidation(Form $form, FormField $formField)
	{

	}

	/**
	 * @param Form      $form
	 * @param FormField $formField
	 */
	public function onValidationError(Form $form, FormField $formField)
	{

	}

	/**
	 * @param Form      $form
	 * @param FormField $formField
	 */
	public function onValidationSuccess(Form $form, FormField $formField)
	{

	}
}
/* EOF */