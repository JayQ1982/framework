<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\FormOptions;
use framework\form\renderer\DefinitionListRenderer;
use framework\form\renderer\LegendAndListRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlText;
use LogicException;

class RadioOptionsField extends OptionsField
{
	const LAYOUT_NONE = 0;
	const LAYOUT_DEFINITIONLIST = 1;
	const LAYOUT_LEGENDANDLIST = 2;

	public function __construct(string $name, HtmlText $label, FormOptions $formOptions, ?string $initialValue, ?HtmlText $requiredError = null, int $layout = RadioOptionsField::LAYOUT_LEGENDANDLIST)
	{
		parent::__construct($name, $label, $formOptions, $initialValue);

		if (is_null($requiredError)) {
			// Mandatory rule: In a field with radio options it is always required to choose one of those options
			$requiredError = HtmlText::encoded('Bitte wählen Sie eine der Optionen aus.');
		}
		$this->addRule(new RequiredRule(defaultErrorMessage: $requiredError));

		if ($layout !== RadioOptionsField::LAYOUT_NONE) {
			switch ($layout) {
				case RadioOptionsField::LAYOUT_DEFINITIONLIST:
					$this->setRenderer(new DefinitionListRenderer($this));
					break;

				case RadioOptionsField::LAYOUT_LEGENDANDLIST:
					$this->setRenderer(new LegendAndListRenderer($this));
					break;
				default:
					throw new LogicException('Invalid layout');
			}
		}
	}
}