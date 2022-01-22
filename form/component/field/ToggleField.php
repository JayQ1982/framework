<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use framework\form\component\FormField;
use framework\form\FormComponent;
use framework\form\FormOptions;
use framework\form\FormRenderer;
use framework\form\renderer\LegendAndListRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;
use LogicException;

class ToggleField extends OptionsField
{
	private array $childrenByMainOption = [];
	private string $defaultChildFieldRenderer = '\framework\form\renderer\DefinitionListRenderer';
	private bool $displayLegend;
	private bool $multiple;
	private ?HtmlText $listDescription = null;

	public function __construct(string $name, HtmlText $label, FormOptions $formOptions, $initialValue, ?HtmlText $requiredError = null, bool $displayLegend = true, bool $multiple = false)
	{
		$this->multiple = $multiple;

		if ($multiple) {
			$initialValue = $this->changeValueToArray($initialValue);
		}
		$this->displayLegend = $displayLegend;

		parent::__construct($name, $label, $formOptions, $initialValue);

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}
	}

	public function setListDescription(?HtmlText $listDescription): void
	{
		$this->listDescription = $listDescription;
	}

	public function setDefaultChildFieldRenderer(string $rendererName): void
	{
		$this->defaultChildFieldRenderer = $rendererName;
	}

	public function addChildField(string $mainOption, FormField $childField): void
	{
		$this->addChildComponent($mainOption, $childField);
	}

	public function addChildComponent(string $mainOption, FormComponent $childComponent): void
	{
		if (!$this->getFormOptions()->exists($mainOption)) {
			throw new LogicException('The mainOption ' . $mainOption . ' does not exist!');
		}
		$childComponent->setParentFormComponent($this);

		$this->childrenByMainOption[$mainOption][$childComponent->getName()] = $childComponent;
	}

	public function getChildField(string $mainOption, string $fieldName): FormField
	{
		$childField = $this->getChildComponent($mainOption, $fieldName);
		if (($childField instanceof FormField) === false) {
			throw new LogicException('The childField ' . $fieldName . ' of mainOption ' . $mainOption . ' is not an instance of FormField');
		}

		return $childField;
	}

	public function getChildComponent(string $mainOption, string $componentName): FormField|FormComponent
	{
		if (!isset($this->childrenByMainOption[$mainOption][$componentName])) {
			throw new LogicException('The mainOption ' . $mainOption . ' has no child ' . $componentName);
		}

		$childComponent = $this->childrenByMainOption[$mainOption][$componentName];

		if (($childComponent instanceof FormComponent) === false) {
			throw new LogicException('The child ' . $componentName . ' of mainOption ' . $mainOption . ' is not an instance of FormComponent');
		}

		return $childComponent;
	}

	public function getChildrenByMainOption(): array
	{
		return $this->childrenByMainOption;
	}

	public function getHtmlTag(): HtmlTag
	{
		$ulTagClasses = ['form-toggle-list'];
		if ($this->hasErrors(withChildElements: false)) {
			$ulTagClasses[] = 'list-has-error';
		}
		$ulTag = new HtmlTag('ul', false, [new HtmlTagAttribute('class', implode(' ', $ulTagClasses), true)]);

		foreach ($this->getFormOptions()->getData() as $key => $htmlText) {
			$combinedSpecifier = $this->getName() . '_' . $key;

			// ... create from inner to outer tag ...

			// Define the attributes for the <input> element:
			$inputAttributes = [
				new HtmlTagAttribute('type', $this->multiple ? 'checkbox' : 'radio', true),
				new HtmlTagAttribute('toggle-id', $combinedSpecifier, true),
				new HtmlTagAttribute('name', $this->multiple ? $this->getName() . '[]' : $this->getName(), true),
				new HtmlTagAttribute('value', $key, true),
			];

			if (isset($this->childrenByMainOption[$key])) {
				$inputAttributes[] = new HtmlTagAttribute('aria-describedby', $combinedSpecifier, true);
			}
			// If that option has been selected, mark it as such with an extra attribute:
			if ($this->multiple) {
				if (in_array($key, $this->getRawValue())) {
					$inputAttributes[] = new HtmlTagAttribute('checked', null, true);
				}
			} else {
				if ((string)$key === (string)$this->getRawValue()) {
					$inputAttributes[] = new HtmlTagAttribute('checked', null, true);
				}
			}
			// Create the Toggle-<input>
			$input = new HtmlTag('input', true, $inputAttributes);

			// Create inner "span-label":
			$spanLabelTag = new HtmlTag('span', false, [new HtmlTagAttribute('class', 'label-text', true)]);
			$spanLabelTag->addText(htmlText: $htmlText);

			// Create the Toggle-<label> element:
			$label = new HtmlTag('label', false);
			// add the Toggle-<input> into Toggle-<label>
			$label->addTag($input);
			$label->addText(HtmlText::encoded(' ' . $spanLabelTag->render()));

			// create -Toggle-<li> tag and add the Toggle-<label> to it
			$li = new HtmlTag('li', false);
			$li->addTag($label);

			// Now add the child fields to that Toggle-<li>-Option:
			if (isset($this->childrenByMainOption[$key])) {
				$div = new HtmlTag('div', false, [
					new HtmlTagAttribute('class', 'form-toggle-content', true),
					new HtmlTagAttribute('id', $combinedSpecifier, true),
				]);
				/** @var FormField $childField */
				foreach ($this->childrenByMainOption[$key] as $childField) {

					$componentRenderer = $childField->getRenderer();
					if (is_null($componentRenderer)) {

						if ($childField instanceof FormField) {
							$childComponentRenderer = new $this->defaultChildFieldRenderer($childField);
						} else {
							$childComponentRenderer = $childField->getDefaultRenderer();
						}
						$childField->setRenderer($childComponentRenderer);
					}
					// Add the child field into the <div>
					$div->addTag($childField->getHtmlTag());
				}
				// Add the <div> with the collected child elements to the <li>-Option
				$li->addTag($div);
			}

			$ulTag->addTag($li);
		}

		// If the ToggleField-Area should NOT have an own "label" named "legend",
		// then only the ToggleField-Area will be returned:
		if (!$this->displayLegend) {
			$divClasses = ['form-element'];
			if ($this->hasErrors(withChildElements: true)) {
				$divClasses[] = 'has-error';
			}
			$divTag = new HtmlTag('div', false, [new HtmlTagAttribute('class', implode(' ', $divClasses), true)]);
			$divTag->addTag($ulTag);

			FormRenderer::addErrorsToParentHtmlTag($this, $divTag);

			return $divTag;
		}

		// A legend is desired left beside the ToggleField-Area:
		$legendAttributes = [];
		if (!$this->isRenderLabel()) {
			$legendAttributes[] = new HtmlTagAttribute('class', 'visuallyhidden', true);
		}

		$legendTag = new HtmlTag('legend', false, $legendAttributes);
		$legendTag->addText($this->getLabel());

		if ($this->isRequired() && $this->isRenderRequiredAbbr()) {
			$abbrTag = new HtmlTag('span', false, [
				new HtmlTagAttribute('class', 'required', true),
			]);
			$abbrTag->addText(HtmlText::encoded('*'));
			$legendTag->addTag($abbrTag);
		}

		$labelInfoText = $this->getLabelInfoText();
		if (!is_null($labelInfoText)) {
			$labelInfoTag = new HtmlTag('i', false, [
				new HtmlTagAttribute('class', 'legend-info', true),
			]);
			$labelInfoTag->addText($labelInfoText);
			$legendTag->addTag($labelInfoTag);
		}

		$fieldsetTag = LegendAndListRenderer::createFieldsetTag($this);
		$fieldsetTag->addTag($legendTag);

		if (!is_null($this->listDescription)) {
			$fieldsetTag->addText(HtmlText::encoded('<div class="fieldset-info">' . $this->listDescription->render() . '</div>'));
		}

		//  the <ul> tag will now be attached
		$fieldsetTag->addTag($ulTag);
		FormRenderer::addErrorsToParentHtmlTag($this, $fieldsetTag);

		return $fieldsetTag;
	}

	public function validate(array $inputData, bool $overwriteValue = true): bool
	{
		// First execute main rules on the toggle field
		parent::validate($inputData, $overwriteValue);
		if ($this->hasErrors(withChildElements: true)) {
			// If we already have an error, return false
			return false;
		}

		$valueAfterValidation = $this->getRawValue();

		// If there is no error so far, we also validate the child fields
		foreach ($this->childrenByMainOption as $mainOption => $children) {
			if ($this->multiple) {
				if (!in_array($mainOption, $valueAfterValidation)) {
					continue;
				}
			} else if ($mainOption != $valueAfterValidation) {
				continue;
			}

			/** @var FormField|FormComponent $formField */
			foreach ($children as $formField) {

				if (($formField instanceof FormField) === false) {
					continue;
				}

				$formField->validate($inputData, $overwriteValue);
			}
		}

		return !$this->hasErrors(withChildElements: true);
	}

	/**
	 * @param null|array|string $value Internally we handle the value as array if it is a multiple field
	 *
	 * @return array|null
	 */
	private function changeValueToArray(null|array|string $value = null): ?array
	{
		if (!is_array($value)) {
			return [$value];
		}

		return $value;
	}

	public function setValue($value): void
	{
		if ($this->multiple) {
			$value = $this->changeValueToArray($value);
		}
		parent::setValue($value);
	}
}