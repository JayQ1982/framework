<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\listener;

use framework\form\component\collection\Form;
use framework\form\component\FormField;

abstract class FormFieldListener
{
	public function onEmptyValueBeforeValidation(Form $form, FormField $formField): void { }

	public function onEmptyValueAfterValidation(Form $form, FormField $formField): void { }

	public function onNotEmptyValueBeforeValidation(Form $form, FormField $formField): void { }

	public function onNotEmptyValueAfterValidation(Form $form, FormField $formField): void { }

	public function onValidationError(Form $form, FormField $formField): void { }

	public function onValidationSuccess(Form $form, FormField $formField): void { }
}