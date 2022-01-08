<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
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