<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\renderer;

use framework\form\component\field\SelectOptionsField;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;
use LogicException;

class SelectOptionsRenderer extends FormRenderer
{
	private bool $acceptMultipleSelections = false;

	public function __construct(private readonly SelectOptionsField $selectOptionsField) { }

	public function prepare(): void
	{
		$selectOptionsField = $this->selectOptionsField;
		$fieldName = $selectOptionsField->getName();
		$selectTag = new HtmlTag(name: 'select', selfClosing: false);
		$selectTag->addHtmlTagAttribute(htmlTagAttribute: new HtmlTagAttribute(
			name: 'name',
			value: $this->acceptMultipleSelections ? $fieldName . '[]' : $fieldName,
			valueIsEncodedForRendering: true
		));
		$selectTag->addHtmlTagAttribute(htmlTagAttribute: new HtmlTagAttribute(
			name: 'id',
			value: $selectOptionsField->getId(),
			valueIsEncodedForRendering: true
		));
		if (count(value: $selectOptionsField->cssClasses) > 0) {
			$selectTag->addHtmlTagAttribute(htmlTagAttribute: new HtmlTagAttribute(
				name: 'class',
				value: implode(separator: ' ', array: $selectOptionsField->cssClasses),
				valueIsEncodedForRendering: true
			));
		}
		if ($selectOptionsField->acceptMultipleSelections) {
			$selectTag->addHtmlTagAttribute(htmlTagAttribute: new HtmlTagAttribute(
				name: 'multiple',
				value: null,
				valueIsEncodedForRendering: true
			));
		}
		$mainOptions = $selectOptionsField->getFormOptions()->getData();
		if ($selectOptionsField->renderEmptyValueOption && !array_key_exists(key: '', array: $mainOptions)) {
			$mainOptions = ['' => $selectOptionsField->emptyValueLabel] + $mainOptions;
		}
		$selectedValue = $selectOptionsField->getRawValue();
		if ($this->acceptMultipleSelections && !is_array(value: $selectedValue)) {
			throw new LogicException(message: 'The selected value must be an array if selection of multiple elements is allowed');
		}
		foreach ($mainOptions as $optiongroupLabelOrOptionValue => $optionGroupArrayOrOptionLabelString) {
			if (is_array(value: $optionGroupArrayOrOptionLabelString)) {
				$selectTag->addTag(htmlTag: $optGroupTag = new HtmlTag(
					name: 'optgroup',
					selfClosing: false,
					htmlTagAttributes: [
						new HtmlTagAttribute(
							name: 'label',
							value: $optiongroupLabelOrOptionValue,
							valueIsEncodedForRendering: true
						),
					]
				));
				foreach ($optionGroupArrayOrOptionLabelString as $optionValue => $optionLabelString) {
					$this->addOptionTag(
						parentTag: $optGroupTag,
						value: $optionValue,
						label: HtmlText::encoded(textContent: $optionLabelString),
						selectedValue: $selectedValue
					);
				}
				continue;
			}
			$this->addOptionTag(
				parentTag: $selectTag,
				value: $optiongroupLabelOrOptionValue,
				label: HtmlText::encoded(textContent: $optionGroupArrayOrOptionLabelString),
				selectedValue: $selectedValue
			);
		}
		$this->setHtmlTag(htmlTag: $selectTag);
	}

	private function addOptionTag(
		HtmlTag               $parentTag,
		string                $value,
		HtmlText              $label,
		array|null|int|string $selectedValue
	): void {
		$attributes = [new HtmlTagAttribute(name: 'value', value: $value, valueIsEncodedForRendering: true)];
		if (
			($this->acceptMultipleSelections && in_array(needle: $value, haystack: $selectedValue))
			|| (!$this->acceptMultipleSelections && $value === (string)$selectedValue)
		) {
			$attributes[] = new HtmlTagAttribute(name: 'selected', value: null, valueIsEncodedForRendering: true);
		}
		$parentTag->addTag(htmlTag: $optionTag = new HtmlTag(name: 'option', selfClosing: false, htmlTagAttributes: $attributes));
		$optionTag->addText(htmlText: $label);
	}
}