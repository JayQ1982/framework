<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\FormOptions;
use framework\form\FormRenderer;
use framework\form\renderer\CheckboxItemRenderer;
use framework\form\renderer\CheckboxOptionsRenderer;
use framework\form\renderer\DefinitionListRenderer;
use framework\form\renderer\LegendAndListRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlText;
use LogicException;

class CheckboxOptionsField extends OptionsField
{
	public const LAYOUT_NONE = 0;
	public const LAYOUT_DEFINITIONLIST = 1;
	public const LAYOUT_LEGENDANDLIST = 2;
	public const LAYOUT_CHECKBOXITEM = 3;

	public function __construct(
		string      $name,
		HtmlText    $label,
		FormOptions $formOptions,
		array       $initialValues,
		?HtmlText   $requiredError = null,
		int         $layout = CheckboxOptionsField::LAYOUT_LEGENDANDLIST
	) {
		parent::__construct(
			name: $name,
			label: $label,
			formOptions: $formOptions,
			initialValue: $initialValues
		);
		$this->acceptArrayAsValue();
		if (!is_null(value: $requiredError)) {
			$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredError));
		}
		if ($layout !== CheckboxOptionsField::LAYOUT_NONE) {
			switch ($layout) {
				case CheckboxOptionsField::LAYOUT_DEFINITIONLIST:
					$this->setRenderer(renderer: new DefinitionListRenderer(formField: $this));
					break;
				case CheckboxOptionsField::LAYOUT_LEGENDANDLIST:
					$this->setRenderer(renderer: new LegendAndListRenderer(optionsField: $this));
					break;
				case CheckboxOptionsField::LAYOUT_CHECKBOXITEM:
					$this->setRenderer(renderer: new CheckboxItemRenderer(checkboxOptionsField: $this));
					break;
				default:
					throw new LogicException(message: 'Invalid layout');
			}
		}
	}

	public function getType(): string
	{
		return 'checkbox';
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new CheckboxOptionsRenderer(checkboxOptionsField: $this);
	}
}