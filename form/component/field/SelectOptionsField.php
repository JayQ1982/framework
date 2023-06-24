<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\FormOptions;
use framework\form\FormRenderer;
use framework\form\renderer\SelectOptionsRenderer;
use framework\form\rule\RequiredRule;
use framework\form\settings\AutoCompleteValue;
use framework\html\HtmlText;

class SelectOptionsField extends OptionsField
{
	public readonly array $cssClasses;
	public readonly HtmlText $emptyValueLabel;

	public function __construct(
		string                  $name,
		HtmlText                $label,
		FormOptions             $formOptions,
		null|string|array       $initialValue,
		?HtmlText               $requiredError = null,
		?HtmlText               $individualEmptyValueLabel = null,
		array                   $cssClasses = [],
		bool                    $renderAsChosenEnhancedField = false,
		public readonly bool    $acceptMultipleSelections = false,
		public readonly bool    $renderEmptyValueOption = true,
		public readonly ?string $placeholder = null,
		?AutoCompleteValue      $autoComplete = null
	) {
		$this->emptyValueLabel = is_null(value: $individualEmptyValueLabel) ? HtmlText::encoded(textContent: '-- Bitte wählen --') : $individualEmptyValueLabel;
		parent::__construct(
			name: $name,
			label: $label,
			formOptions: $formOptions,
			initialValue: $initialValue,
			autoComplete: $autoComplete
		);
		if (!is_null(value: $requiredError)) {
			$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredError));
		}
		if ($renderAsChosenEnhancedField) {
			$cssClasses[] = 'chosen';
		}
		$this->cssClasses = $cssClasses;
		if ($this->acceptMultipleSelections) {
			$this->acceptArrayAsValue();
		}
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new SelectOptionsRenderer(selectOptionsField: $this);
	}
}