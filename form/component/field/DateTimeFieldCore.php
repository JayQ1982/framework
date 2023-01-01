<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use DateTimeImmutable;
use framework\datacheck\Sanitizer;
use framework\html\HtmlEncoder;
use Throwable;

abstract class DateTimeFieldCore extends TextField
{
	private string $renderValueFormat;

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
		if ($this->hasErrors(withChildElements: false)) {
			// Invalid value; show original input
			return HtmlEncoder::encode(value: Sanitizer::trimmedString(input: $originalValue));
		}
		try {
			return (new DateTimeImmutable(datetime: $originalValue))->format(format: $this->renderValueFormat);
		} catch (Throwable) {
			// Should not be reached. Anyway ... invalid value; show original input
			return HtmlEncoder::encode(value: Sanitizer::trimmedString(input: $originalValue));
		}
	}
}