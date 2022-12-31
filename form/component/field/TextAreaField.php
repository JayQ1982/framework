<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\form\renderer\TextAreaRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlEncoder;
use framework\html\HtmlText;

class TextAreaField extends FormField
{
	private int $rows;
	private int $cols;
	private ?string $placeholder = null;
	private array $cssClassesForRenderer = [];

	public function __construct(string $name, HtmlText $label, null|string|array $value = null, ?HtmlText $requiredError = null, int $rows = 4, int $cols = 50)
	{
		$this->rows = $rows;
		$this->cols = $cols;

		parent::__construct($name, $label, $value);

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}
	}

	public function addCssClassForRenderer(string $className)
	{
		$this->cssClassesForRenderer[] = $className;
	}

	public function getCssClassesForRenderer(): array
	{
		return $this->cssClassesForRenderer;
	}

	public function setPlaceholder(string $placeholder)
	{
		$this->placeholder = $placeholder;
	}

	public function getRows(): int
	{
		return $this->rows;
	}

	public function getCols(): int
	{
		return $this->cols;
	}

	public function getPlaceholder(): ?string
	{
		return $this->placeholder;
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new TextAreaRenderer($this);
	}

	public function renderValue(): string
	{
		$currentValue = $this->getRawValue();
		if (is_null($currentValue)) {
			return '';
		}
		if (is_array($currentValue)) {
			$htmlArray = [];
			foreach ($currentValue as $row) {
				$htmlArray[] = HtmlEncoder::encode(value: $row);
			}

			return implode(separator: PHP_EOL, array: $htmlArray);
		}

		return HtmlEncoder::encode(value: $currentValue);
	}
}