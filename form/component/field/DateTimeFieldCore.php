<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\component\field;

use DateTime;
use framework\html\HtmlDocument;
use Throwable;

/**
 * Hint: use derived Class "DateField" or "TimeField" in forms
 */
abstract class DateTimeFieldCore extends TextField
{
	protected string $renderValueFormat;

	public function setRenderValueFormat(string $renderValueFormat): void
	{
		$this->renderValueFormat = $renderValueFormat;
	}

	public function renderValue(): string
	{
		if ($this->isValueEmpty()) {
			return '';
		}
		$originalValue = $this->getRawValue();
		if ($this->hasErrors()) {
			// Invalid value; show original input
			return HtmlDocument::htmlEncode(trim($originalValue));
		}
		try {
			$dateTime = new DateTime($originalValue);

			return $dateTime->format($this->renderValueFormat);
		} catch (Throwable) {
			// Should not be reached. Anyway ... invalid value; show original input
			return HtmlDocument::htmlEncode(trim($originalValue));
		}
	}
}