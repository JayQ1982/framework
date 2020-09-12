<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use LogicException;
use framework\form\renderer\CheckboxItemRenderer;
use framework\form\renderer\DefinitionListRenderer;
use framework\form\renderer\LegendAndListRenderer;
use framework\form\rule\RequiredRule;

class CheckboxOptionsField extends OptionsField
{
	const LAYOUT_NONE = 0;
	const LAYOUT_DEFINITIONLIST = 1;
	const LAYOUT_LEGENDANDLIST = 2;
	const LAYOUT_CHECKBOXITEM = 3;

	public function __construct(string $name, string $label, array $options, bool $optionsAreHTML, $value, ?string $requiredError = null, int $layout = self::LAYOUT_LEGENDANDLIST)
	{
		parent::__construct($name, $label, $options, $optionsAreHTML, $value);
		$this->acceptArrayAsValue();

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}

		if ($layout !== self::LAYOUT_NONE) {
			switch ($layout) {
				case self::LAYOUT_DEFINITIONLIST:
					$this->setRenderer(new DefinitionListRenderer($this));
					break;

				case self::LAYOUT_LEGENDANDLIST:
					$this->setRenderer(new LegendAndListRenderer($this));
					break;

				case self::LAYOUT_CHECKBOXITEM:
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
/* EOF */