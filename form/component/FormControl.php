<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component;

use framework\form\FormComponent;
use framework\form\FormRenderer;
use framework\form\renderer\FormControlRenderer;
use framework\html\HtmlText;

class FormControl extends FormComponent
{
	private HtmlText $submitLabel;
	private ?string $cancelLink;
	private HtmlText $cancelLabel;

	public function __construct(string $name, HtmlText $submitLabel, ?string $cancelLink = null, ?HtmlText $cancelLabel = null)
	{
		$this->submitLabel = $submitLabel;
		$this->cancelLink = $cancelLink;
		$this->cancelLabel = is_null($cancelLabel) ? HtmlText::encoded('Abbrechen') : $cancelLabel;

		parent::__construct($name);
	}

	public function getSubmitLabel(): HtmlText
	{
		return $this->submitLabel;
	}

	public function getCancelLink(): ?string
	{
		return $this->cancelLink;
	}

	public function getCancelLabel(): HtmlText
	{
		return $this->cancelLabel;
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new FormControlRenderer($this);
	}
}