<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\field\RadioOptionsField;

class RadioOptionsRenderer extends DefaultOptionsRenderer
{
	private RadioOptionsField $radioOptionsField;

	public function __construct(RadioOptionsField $radioOptionsField)
	{
		$this->radioOptionsField = $radioOptionsField;
		parent::__construct($radioOptionsField, 'radio', false);
	}
}