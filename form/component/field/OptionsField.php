<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use framework\form\component\FormField;
use framework\form\rule\ValidateAgainstOptions;

abstract class OptionsField extends FormField
{
	protected array $options = [];
	protected bool $optionsAreHTML;

	public function __construct(string $name, string $label, array $options, bool $optionsAreHTML, $value)
	{
		$this->options = $options;
		$this->optionsAreHTML = $optionsAreHTML;
		parent::__construct($name, $label, $value);
		// We set a default error message because in normal circumstance this case cannot happen if the user chooses
		// available options, so it doesn't make sense to always set an individual error message for this check.
		// It can only happen by data manipulation, which we don't want to be notified about (by exception).
		$this->addRule(new ValidateAgainstOptions('Selected invalid value in field ' . $name, $this->options));
	}

	public function getOptions(): array
	{
		return $this->options;
	}

	public function setOptions(array $options): void
	{
		$this->options = $options;
	}

	public function isOptionsHTML(): bool
	{
		return $this->optionsAreHTML;
	}
}
/* EOF */