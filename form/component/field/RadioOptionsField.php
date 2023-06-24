<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\FormOptions;
use framework\form\FormRenderer;
use framework\form\renderer\DefinitionListRenderer;
use framework\form\renderer\LegendAndListRenderer;
use framework\form\renderer\RadioOptionsRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlText;
use LogicException;

class RadioOptionsField extends OptionsField
{
	public const LAYOUT_NONE = 0;
	public const LAYOUT_DEFINITIONLIST = 1;
	public const LAYOUT_LEGENDANDLIST = 2;

	public function __construct(
		string      $name,
		HtmlText    $label,
		FormOptions $formOptions,
		?string     $initialValue,
		?HtmlText   $requiredError = null,
		int         $layout = RadioOptionsField::LAYOUT_LEGENDANDLIST
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
		if ($layout !== RadioOptionsField::LAYOUT_NONE) {
			switch ($layout) {
				case RadioOptionsField::LAYOUT_DEFINITIONLIST:
					$this->setRenderer(renderer: new DefinitionListRenderer(formField: $this));
					break;

				case RadioOptionsField::LAYOUT_LEGENDANDLIST:
					$this->setRenderer(renderer: new LegendAndListRenderer(optionsField: $this));
					break;
				default:
					throw new LogicException(message: 'Invalid layout');
			}
		}
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new RadioOptionsRenderer(radioOptionsField: $this);
	}
}