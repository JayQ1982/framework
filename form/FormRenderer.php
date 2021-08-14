<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form;

use LogicException;
use framework\form\component\FormField;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;

abstract class FormRenderer
{
	private ?HtmlTag $htmlTag = null; // The base Tag-Element for this renderer, which may contain child-elements

	/** The descending classes must use this method to prepare the base Tag-Element */
	abstract public function prepare(): void;

	/**
	 * Method to set the base Tag-Element. It's not allowed to overwrite it, if already set!
	 *
	 * @param HtmlTag $htmlTag : The Tag-Element to be set
	 */
	protected function setHtmlTag(HtmlTag $htmlTag): void
	{
		if (!is_null($this->htmlTag)) {
			throw new LogicException('You cannot overwrite an already defined Tag-Element.');
		}
		$this->htmlTag = $htmlTag;
	}

	/**
	 * Get the current base Tag-Element for this renderer
	 *
	 * @return HtmlTag|null : The current base Tag-Element or null, if not set
	 */
	public function getHtmlTag(): ?HtmlTag
	{
		return $this->htmlTag;
	}

	/**
	 * Get the html-code which can be used for output
	 *
	 * @return string : The html code (rendered by the base Tag-Element and it's children) - Empty string if base Tag-Element is not set
	 */
	public function getHTML(): string
	{
		return is_null($this->htmlTag) ? '' : $this->htmlTag->render();
	}

	public static function addErrorsToParentHtmlTag(FormComponent $formComponentWithErrors, HtmlTag $parentHtmlTag): void
	{
		if (!$formComponentWithErrors->hasErrors(withChildElements: false)) {
			return;
		}

		$divTag = new HtmlTag('div', false, [
			new HtmlTagAttribute('class', 'form-input-error', true),
			new HtmlTagAttribute('id', $formComponentWithErrors->getName() . '-error', true),
		]);
		$errorsHTML = [];
		foreach ($formComponentWithErrors->getErrorsAsHtmlTextObjects() as $htmlText) {
			$errorsHTML[] = $htmlText->render();
		}
		$divTag->addText(new HtmlText(implode('<br>', $errorsHTML), true));
		$parentHtmlTag->addTag($divTag);
	}

	public static function addFieldInfoToParentHtmlTag(FormField $formFieldWithFieldInfo, HtmlTag $parentHtmlTag): void
	{
		$divTag = new HtmlTag('div', false, [
			new HtmlTagAttribute('class', 'form-input-info', true),
			new HtmlTagAttribute('id', $formFieldWithFieldInfo->getName() . '-info', true),
		]);
		$divTag->addText($formFieldWithFieldInfo->getFieldInfo());
		$parentHtmlTag->addTag($divTag);
	}

	public static function addAriaAttributesToHtmlTag(FormField $formField, HtmlTag $parentHtmlTag): void
	{
		$ariaDescribedBy = [];

		if ($formField->hasErrors(withChildElements: false)) {
			$parentHtmlTag->addHtmlTagAttribute(new HtmlTagAttribute('aria-invalid', 'true', true));
			$ariaDescribedBy[] = $formField->getName() . '-error';
		}

		if (!is_null($formField->getFieldInfo())) {
			$ariaDescribedBy[] = $formField->getName() . '-info';
		}
		if (count($ariaDescribedBy) > 0) {
			$parentHtmlTag->addHtmlTagAttribute(new HtmlTagAttribute('aria-describedby', implode(' ', $ariaDescribedBy), true));
		}
	}
}