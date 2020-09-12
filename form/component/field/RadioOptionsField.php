<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use LogicException;
use framework\form\renderer\DefinitionListRenderer;
use framework\form\renderer\LegendAndListRenderer;
use framework\form\rule\RequiredRule;

class RadioOptionsField extends OptionsField
{
	const LAYOUT_NONE = 0;
	const LAYOUT_DEFINITIONLIST = 1;
	const LAYOUT_LEGENDANDLIST = 2;

	public function __construct(string $name, string $label, array $options, bool $optionsAreHTML, $value, string $requiredError = 'Bitte wählen Sie eine der Optionen aus.', int $layout = self::LAYOUT_LEGENDANDLIST)
	{
		parent::__construct($name, $label, $options, $optionsAreHTML, $value);

		// Mandatory rule: In a field with radio options it is always required to choose one of those options
		$this->addRule(new RequiredRule($requiredError));

		if ($layout !== self::LAYOUT_NONE) {
			switch ($layout) {
				case self::LAYOUT_DEFINITIONLIST:
					$this->setRenderer(new DefinitionListRenderer($this));
					break;

				case self::LAYOUT_LEGENDANDLIST:
					$this->setRenderer(new LegendAndListRenderer($this));
					break;
				default:
					throw new LogicException('Invalid layout');
			}
		}
	}
}
/* EOF */