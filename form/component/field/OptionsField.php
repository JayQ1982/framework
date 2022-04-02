<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\component\FormField;
use framework\form\FormOptions;
use framework\form\rule\ValidateAgainstOptions;
use framework\html\HtmlText;

abstract class OptionsField extends FormField
{
	private FormOptions $formOptions;
	private array $listTagClasses = [];

	public function __construct(string $name, HtmlText $label, FormOptions $formOptions, $initialValue)
	{
		$this->formOptions = $formOptions;
		parent::__construct($name, $label, $initialValue);
		// We set a default error message because in normal circumstance this case cannot happen if the user chooses
		// available options, so it doesn't make sense to always set an individual error message for this check.
		// It can only happen by data manipulation, which we don't want to be notified about (by exception).
		$this->addRule(new ValidateAgainstOptions(HtmlText::encoded('Selected invalid value in field ' . $name), $this->formOptions));
	}

	public function getFormOptions(): FormOptions
	{
		return $this->formOptions;
	}

	public function setFormOptions(FormOptions $formOptions): void
	{
		$this->formOptions = $formOptions;
	}

	public function addListTagClass(string $className): void
	{
		$this->listTagClasses[] = $className;
	}

	public function getListTagClasses(): array
	{
		return array_unique($this->listTagClasses);
	}
}