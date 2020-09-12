<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component;

use framework\form\FormComponent;
use framework\form\FormRenderer;
use framework\form\renderer\FormControlRenderer;

class FormControl extends FormComponent
{
	private string $submitLabel;
	private ?string $cancelLink;
	private string $cancelLabel;

	public function __construct(string $name, string $submitLabel, ?string $cancelLink = null, string $cancelLabel = 'Abbrechen')
	{
		$this->submitLabel = $submitLabel;
		$this->cancelLink = $cancelLink;
		$this->cancelLabel = $cancelLabel;

		parent::__construct($name);
	}

	public function getSubmitLabel(): string
	{
		return $this->submitLabel;
	}

	public function getCancelLink(): ?string
	{
		return $this->cancelLink;
	}

	public function getCancelLabel(): string
	{
		return $this->cancelLabel;
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new FormControlRenderer($this);
	}
}
/* EOF */