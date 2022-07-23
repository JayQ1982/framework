<?php
/**
 * @author    Christof Moser <framework@actra.ch>
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
		parent::__construct(defaultErrorMessage: HtmlText::encoded(
			textContent: 'Das Formular konnte wegen eines technischen Problems (ungültiges CSRF) nicht übermittelt werden. Bitte versuchen Sie es erneut.'
		));
	}

	public function validate(FormField $formField): bool
	{
		$token = $formField->getRawValue();
		if (is_null(value: $token)) {
			$token = array_key_exists(key: CsrfToken::getFieldName(), array: $_GET) ? $_GET[CsrfToken::getFieldName()] : '';
		}

		return CsrfToken::validateToken(token: $token);
	}
}