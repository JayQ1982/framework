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
use framework\html\HtmlText;

class SelectOptionsField extends OptionsField
{
	public readonly array $cssClasses;
	public readonly HtmlText $emptyValueLabel;

	public function __construct(
		string               $name,
		HtmlText             $label,
		FormOptions          $formOptions,
		null|string|array    $initialValue,
		?HtmlText            $requiredError = null,
		?HtmlText            $individualEmptyValueLabel = null,
		array                $cssClasses = [],
		bool                 $renderAsChosenEnhancedField = false,
		public readonly bool $acceptMultipleSelections = false,
		public readonly bool $renderEmptyValueOption = true
	) {
		$this->emptyValueLabel = is_null(value: $individualEmptyValueLabel) ? HtmlText::encoded(textContent: '-- Bitte wählen --') : $individualEmptyValueLabel;
		parent::__construct(
			name: $name,
			label: $label,
			formOptions: $formOptions,
			initialValue: $initialValue
		);
		if (!is_null(value: $requiredError)) {
			$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredError));
		}
		if ($renderAsChosenEnhancedField) {
			$cssClasses[] = 'chosen';
		}
		$this->cssClasses = $cssClasses;
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new SelectOptionsRenderer(selectOptionsField: $this);
	}
}