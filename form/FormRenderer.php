<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form;

use framework\form\component\FormField;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;
use LogicException;

abstract class FormRenderer
{
	private ?HtmlTag $htmlTag = null; // The base Tag-Element for this renderer, which may contain child-elements

	/** The descending classes must use this method to prepare the base Tag-Element */
	abstract public function prepare(): void;

	/**
	 * Method to set the base Tag-Element. It's not allowed to overwrite it, if already set!
	 *
	 * @param HtmlTag $htmlTag The Tag-Element to be set
	 */
	protected function setHtmlTag(HtmlTag $htmlTag): void
	{
		if (!is_null(value: $this->htmlTag)) {
			throw new LogicException(message: 'You cannot overwrite an already defined Tag-Element.');
		}
		$this->htmlTag = $htmlTag;
	}

	/**
	 * Get the current base Tag-Element for this renderer
	 *
	 * @return HtmlTag|null The current base Tag-Element or null, if not set
	 */
	public function getHtmlTag(): ?HtmlTag
	{
		return $this->htmlTag;
	}

	public static function addErrorsToParentHtmlTag(FormComponent $formComponentWithErrors, HtmlTag $parentHtmlTag): void
	{
		if (!$formComponentWithErrors->hasErrors(withChildElements: false)) {
			return;
		}
		$divTag = new HtmlTag(name: 'div', selfClosing: false, htmlTagAttributes: [
			new HtmlTagAttribute(name: 'class', value: 'form-input-error', valueIsEncodedForRendering: true),
			new HtmlTagAttribute(name: 'id', value: $formComponentWithErrors->getName() . '-error', valueIsEncodedForRendering: true),
		]);
		$errorsHTML = [];
		foreach ($formComponentWithErrors->getErrorsAsHtmlTextObjects() as $htmlText) {
			$errorsHTML[] = $htmlText->render();
		}
		$divTag->addText(htmlText: HtmlText::encoded(textContent: implode(separator: '<br>', array: $errorsHTML)));
		$parentHtmlTag->addTag(htmlTag: $divTag);
	}

	public static function addFieldInfoToParentHtmlTag(FormField $formFieldWithFieldInfo, HtmlTag $parentHtmlTag): void
	{
		$divTag = new HtmlTag(name: 'div', selfClosing: false, htmlTagAttributes: [
			new HtmlTagAttribute(name: 'class', value: 'form-input-info', valueIsEncodedForRendering: true),
			new HtmlTagAttribute(name: 'id', value: $formFieldWithFieldInfo->getName() . '-info', valueIsEncodedForRendering: true),
		]);
		$divTag->addText(htmlText: $formFieldWithFieldInfo->getFieldInfo());
		$parentHtmlTag->addTag(htmlTag: $divTag);
	}

	public static function addAriaAttributesToHtmlTag(FormField $formField, HtmlTag $parentHtmlTag): void
	{
		$ariaDescribedBy = [];
		if ($formField->hasErrors(withChildElements: false)) {
			$parentHtmlTag->addHtmlTagAttribute(htmlTagAttribute: new HtmlTagAttribute(
				name: 'aria-invalid',
				value: 'true',
				valueIsEncodedForRendering: true
			));
			$ariaDescribedBy[] = $formField->getName() . '-error';
		}
		if (!is_null(value: $formField->getFieldInfo())) {
			$ariaDescribedBy[] = $formField->getName() . '-info';
		}
		if (count(value: $ariaDescribedBy) > 0) {
			$parentHtmlTag->addHtmlTagAttribute(htmlTagAttribute: new HtmlTagAttribute(
				name: 'aria-describedby',
				value: implode(separator: ' ', array: $ariaDescribedBy),
				valueIsEncodedForRendering: true
			));
		}
	}
}