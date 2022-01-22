<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\rule;

use framework\form\component\FormField;
use framework\form\FormRule;
use framework\html\HtmlText;
use framework\security\CsrfToken;

class ValidCsrfTokenValue extends FormRule
{
	public function __construct()
	{
		// Hidden fields have no own visible error label
		parent::__construct(HtmlText::encoded('Das Formular konnte wegen einem technischen Problem (ungültiges CSRF) nicht übermittelt werden. Bitte versuchen Sie es erneut.'));
	}

	public function validate(FormField $formField): bool
	{
		$token = $formField->getRawValue();
		if (is_null($token)) {
			$token = $_GET[CsrfToken::getFieldName()] ?? '';
		}

		return CsrfToken::validateToken($token);
	}
}