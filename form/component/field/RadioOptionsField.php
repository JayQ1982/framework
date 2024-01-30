<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\component\layout\RadioOptionsLayout;
use framework\form\FormOptions;
use framework\form\FormRenderer;
use framework\form\renderer\DefinitionListRenderer;
use framework\form\renderer\LegendAndListRenderer;
use framework\form\renderer\RadioOptionsRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlText;

class RadioOptionsField extends OptionsField
{
	public function __construct(
		string             $name,
		HtmlText           $label,
		FormOptions        $formOptions,
		?string            $initialValue,
		?HtmlText          $requiredError = null,
		RadioOptionsLayout $layout = RadioOptionsLayout::LEGEND_AND_LIST
	) {
		parent::__construct(
			name: $name,
			label: $label,
			formOptions: $formOptions,
			initialValue: $initialValue,
			autoComplete: null
		);
		if (is_null(value: $requiredError)) {
			// Mandatory rule: In a field with radio options it is always required to choose one of those options
			$requiredError = HtmlText::encoded(textContent: 'Bitte wählen Sie eine der Optionen aus.');
		}
		$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredError));
		switch ($layout) {
			case RadioOptionsLayout::DEFINITION_LIST:
				$this->setRenderer(renderer: new DefinitionListRenderer(formField: $this));
				break;

			case RadioOptionsLayout::LEGEND_AND_LIST:
				$this->setRenderer(renderer: new LegendAndListRenderer(optionsField: $this));
				break;
			case RadioOptionsLayout::NONE:
				break;
		}
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new RadioOptionsRenderer(radioOptionsField: $this);
	}
}