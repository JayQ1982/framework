<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\rule;

use framework\form\component\CSRFtoken;
use framework\form\component\FormField;
use framework\form\FormRule;

class ValidCSRFtokenValue extends FormRule
{
	public function __construct()
	{
		// Hidden fields have no own visible error label
		parent::__construct('Das Formular konnte wegen einem technischen Problem (ungültiges CSRF) nicht übermittelt werden. Bitte versuchen Sie es erneut.');
	}

	public function validate(FormField $formField): bool
	{
		$token = $formField->getRawValue();
		if (is_null($token)) {
			$token = $_GET[CSRFtoken::getFieldName()] ?? '';
		}

		return CSRFtoken::validateToken($token);
	}
}
/* EOF */