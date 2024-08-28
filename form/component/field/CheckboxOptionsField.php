<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\component\layout\CheckboxOptionsLayout;
use framework\form\FormOptions;
use framework\form\FormRenderer;
use framework\form\renderer\CheckboxItemRenderer;
use framework\form\renderer\CheckboxOptionsRenderer;
use framework\form\renderer\DefinitionListRenderer;
use framework\form\renderer\LegendAndListRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlText;

class CheckboxOptionsField extends OptionsField
{
	public function __construct(
		string                $name,
		HtmlText              $label,
		FormOptions           $formOptions,
		array                 $initialValues,
		?HtmlText             $requiredError = null,
		CheckboxOptionsLayout $layout = CheckboxOptionsLayout::LEGEND_AND_LIST
	) {
		parent::__construct(
			name: $name,
			label: $label,
			formOptions: $formOptions,
			initialValue: $initialValues,
			autoComplete: null
		);
		$this->acceptArrayAsValue();
		if (!is_null(value: $requiredError)) {
			$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredError));
		}
		switch ($layout) {
			case CheckboxOptionsLayout::DEFINITION_LIST:
				$this->setRenderer(renderer: new DefinitionListRenderer(formField: $this));
				break;
			case CheckboxOptionsLayout::LEGEND_AND_LIST:
				$this->setRenderer(renderer: new LegendAndListRenderer(optionsField: $this));
				break;
			case CheckboxOptionsLayout::CHECKBOX_ITEM:
				$this->setRenderer(renderer: new CheckboxItemRenderer(checkboxOptionsField: $this));
				break;
			case CheckboxOptionsLayout::NONE:
				break;
		}
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new CheckboxOptionsRenderer(checkboxOptionsField: $this);
	}
}