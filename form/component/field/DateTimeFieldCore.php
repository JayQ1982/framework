<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use DateTime;
use framework\form\FormRenderer;
use Throwable;

/**
 * Hint: use derived Class "DateField" or "TimeField" in forms
 */
abstract class DateTimeFieldCore extends TextField
{
	protected string $renderValueFormat;

	public function setRenderValueFormat(string $renderValueFormat)
	{
		$this->renderValueFormat = $renderValueFormat;
	}

	/**
	 * Returns the value as proper encoded HTML string
	 *
	 * @return string
	 */
	public function renderValue(): string
	{
		if ($this->isValueEmpty()) {
			return '';
		}
		$originalValue = $this->getRawValue();
		if ($this->hasErrors()) {
			// Invalid value; show original input
			return FormRenderer::htmlEncode(trim($originalValue));
		}
		try {
			$dateTime = new DateTime($originalValue);

			return $dateTime->format($this->renderValueFormat);
		} catch (Throwable $e) {
			// Should not be reached. Anyway ... invalid value; show original input
			return FormRenderer::htmlEncode(trim($originalValue));
		}
	}
}
/* EOF */