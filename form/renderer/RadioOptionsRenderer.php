<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\field\RadioOptionsField;

class RadioOptionsRenderer extends DefaultOptionsRenderer
{
	public function __construct(RadioOptionsField $radioOptionsField)
	{
		parent::__construct(optionsField: $radioOptionsField, inputFieldType: 'radio', acceptMultipleValues: false);
	}
}