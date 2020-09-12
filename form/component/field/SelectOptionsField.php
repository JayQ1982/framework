<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use framework\form\FormRenderer;
use framework\form\renderer\SelectOptionsRenderer;
use framework\form\rule\RequiredRule;

class SelectOptionsField extends OptionsField
{
	private string $emptyValueLabel;
	private bool $chosen;
	private bool $multi;
	private ?string $onchange;
	private ?int $size = null;
	private bool $hasEmptyValue = true;

	public function __construct(string $name, string $label, array $options, $value, ?string $requiredError = null, string $emptyValueLabel = '', bool $chosen = false, bool $multi = false, ?string $onChange = null)
	{
		$this->emptyValueLabel = $emptyValueLabel;
		$this->chosen = $chosen;
		$this->multi = $multi;
		$this->onchange = $onChange;

		// Force optionsAreHTML to false because there are no html-tags allowed in option-tags
		parent::__construct($name, $label, $options, false, $value);

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}
	}

	public function setSize(?int $size): void
	{
		$this->size = $size;
	}

	public function getSize(): ?int
	{
		return $this->size;
	}

	public function setHasEmptyValue(bool $value): void
	{
		$this->hasEmptyValue = $value;
	}

	public function hasEmptyValue(): bool
	{
		return $this->hasEmptyValue;
	}

	public function getDefaultRenderer(): FormRenderer
	{
		$renderer = new SelectOptionsRenderer($this);
		$renderer->setChosen($this->chosen);
		$renderer->setMulti($this->multi);
		$renderer->setOnChange($this->onchange);

		return $renderer;
	}

	public function getEmptyValueLabel(): string
	{
		return $this->emptyValueLabel;
	}

	public function setEmptyValueLabel(string $emptyValueLabel)
	{
		$this->emptyValueLabel = $emptyValueLabel;
	}
}
/* EOF */