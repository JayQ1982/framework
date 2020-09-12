<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use framework\form\component\CSRFtoken;
use framework\form\FormTag;

final class CSRFtokenField extends HiddenField
{
	public function __construct()
	{
		parent::__construct(CSRFtoken::getFieldName());
	}

	public function getFormTag(): ?FormTag
	{
		$this->setValue(CSRFtoken::getToken());

		return parent::getFormTag();
	}
}
/* EOF */