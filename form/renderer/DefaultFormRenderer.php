<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\renderer;

use framework\form\component\collection\Form;
use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;

class DefaultFormRenderer extends FormRenderer
{
	public function __construct(private readonly Form $form) { }

	public function prepare(): void
	{
		$form = $this->form;
		$attributes = [
			new HtmlTagAttribute(
				name: 'method',
				value: ($form->isMethodPost() ? 'post' : 'get'),
				valueIsEncodedForRendering: true
			),
			new HtmlTagAttribute(
				name: 'action',
				value: '?' . $form->getSentIndicator(),
				valueIsEncodedForRendering: true
			),
		];
		$cssClasses = $form->getCssClasses();
		if (count(value: $cssClasses) > 0) {
			$attributes[] = new HtmlTagAttribute(
				name: 'class',
				value: implode(separator: ' ', array: $cssClasses),
				valueIsEncodedForRendering: true
			);
		}
		if ($form->acceptUpload()) {
			$attributes[] = new HtmlTagAttribute(
				name: 'enctype',
				value: 'multipart/form-data',
				valueIsEncodedForRendering: true
			);
		}
		$htmlTag = new HtmlTag(name: 'form', selfClosing: false, htmlTagAttributes: $attributes);
		$this->renderErrors(htmlTag: $htmlTag);
		foreach ($form->getChildComponents() as $childComponent) {
			$componentRenderer = $childComponent->getRenderer();
			if (is_null(value: $componentRenderer)) {
				if ($childComponent instanceof FormField) {
					$childComponentRenderer = $form->getDefaultFormFieldRenderer(formField: $childComponent);
				} else {
					$childComponentRenderer = $childComponent->getDefaultRenderer();
				}
				$childComponent->setRenderer(renderer: $childComponentRenderer);
			}
			$htmlTag->addTag(htmlTag: $childComponent->getHtmlTag());
		}
		$this->setHtmlTag(htmlTag: $htmlTag);
	}

	private function renderErrors(HtmlTag $htmlTag): void
	{
		$form = $this->form;
		if (!$form->hasErrors(withChildElements: true)) {
			return;
		}
		$errorsAsHtmlTextObjects = $form->getErrorsAsHtmlTextObjects();
		$amountOfErrors = count(value: $errorsAsHtmlTextObjects);
		if ($amountOfErrors === 0) {
			return;
		}
		$mainAttributes = [
			new HtmlTagAttribute(name: 'class', value: 'form-error', valueIsEncodedForRendering: true),
		];
		if ($amountOfErrors === 1) {
			$htmlTag->addTag(htmlTag: $pTag = new HtmlTag(name: 'p', selfClosing: false, htmlTagAttributes: $mainAttributes));
			$pTag->addTag(htmlTag: $bTag = new HtmlTag(name: 'b', selfClosing: false));
			$bTag->addText(htmlText: current(array: $errorsAsHtmlTextObjects));

			return;
		}
		$htmlTag->addTag(htmlTag: $divTag = new HtmlTag(name: 'div', selfClosing: false, htmlTagAttributes: $mainAttributes));
		$divTag->addTag(htmlTag: $ulTag = new HtmlTag(name: 'ul', selfClosing: false));
		foreach ($errorsAsHtmlTextObjects as $htmlText) {
			$ulTag->addTag(htmlTag: $liTag = new HtmlTag(name: 'li', selfClosing: false));
			$liTag->addText(htmlText: $htmlText);
		}
	}
}