<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\component\field;

use framework\form\FormOptions;
use framework\form\FormRenderer;
use framework\form\renderer\SelectOptionsRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlText;

class SelectOptionsField extends OptionsField
{
	private HtmlText $emptyValueLabel;
	private bool $renderAsChosenEnhancedField = false;
	private bool $acceptMultipleSelections = false;
	private bool $renderEmptyValueOption = true;

	public function __construct(string $name, HtmlText $label, FormOptions $formOptions, null|string|array $initialValue, ?HtmlText $requiredError = null, ?HtmlText $individualEmptyValueLabel = null)
	{
		$this->emptyValueLabel = is_null($individualEmptyValueLabel) ? new HtmlText('-- Bitte wählen --', true) : $individualEmptyValueLabel;

		parent::__construct($name, $label, $formOptions, $initialValue);

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}
	}

	public function setRenderAsChosenEnhancedField(bool $renderAsChosenEnhancedField): void
	{
		$this->renderAsChosenEnhancedField = $renderAsChosenEnhancedField;
	}

	public function setAcceptMultipleSelections(bool $acceptMultipleSelections): void
	{
		$this->acceptMultipleSelections = $acceptMultipleSelections;
	}

	public function setRenderEmptyValueOption(bool $renderEmptyValueOption): void
	{
		$this->renderEmptyValueOption = $renderEmptyValueOption;
	}

	public function getDefaultRenderer(): FormRenderer
	{
		$renderer = new SelectOptionsRenderer($this);
		$renderer->setChosen($this->renderAsChosenEnhancedField);
		$renderer->setMulti($this->acceptMultipleSelections);

		return $renderer;
	}

	public function getEmptyValueLabel(): HtmlText
	{
		return $this->emptyValueLabel;
	}

	public function isRenderEmptyValueOption(): bool
	{
		return $this->renderEmptyValueOption;
	}
}