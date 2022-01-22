<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\FormOptions;
use framework\form\renderer\CheckboxItemRenderer;
use framework\form\renderer\DefinitionListRenderer;
use framework\form\renderer\LegendAndListRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlText;
use LogicException;

class CheckboxOptionsField extends OptionsField
{
	const LAYOUT_NONE = 0;
	const LAYOUT_DEFINITIONLIST = 1;
	const LAYOUT_LEGENDANDLIST = 2;
	const LAYOUT_CHECKBOXITEM = 3;

	public function __construct(string $name, HtmlText $label, FormOptions $formOptions, array $initialValues, ?HtmlText $requiredError = null, int $layout = CheckboxOptionsField::LAYOUT_LEGENDANDLIST)
	{
		parent::__construct($name, $label, $formOptions, $initialValues);
		$this->acceptArrayAsValue();

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}

		if ($layout !== CheckboxOptionsField::LAYOUT_NONE) {
			switch ($layout) {
				case CheckboxOptionsField::LAYOUT_DEFINITIONLIST:
					$this->setRenderer(new DefinitionListRenderer($this));
					break;

				case CheckboxOptionsField::LAYOUT_LEGENDANDLIST:
					$this->setRenderer(new LegendAndListRenderer($this));
					break;

				case CheckboxOptionsField::LAYOUT_CHECKBOXITEM:
					$this->setRenderer(new CheckboxItemRenderer($this));
					break;

				default:
					throw new LogicException('Invalid layout');
			}
		}
	}

	public function getType(): string
	{
		return 'checkbox';
	}
}