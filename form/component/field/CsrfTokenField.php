<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\html\HtmlTag;
use framework\security\CsrfToken;

final class CsrfTokenField extends HiddenField
{
	public function __construct()
	{
		parent::__construct(CsrfToken::getFieldName());
	}

	public function getHtmlTag(): ?HtmlTag
	{
		$this->setValue(CsrfToken::getToken());

		return parent::getHtmlTag();
	}
}