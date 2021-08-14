<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\collection\Form;
use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;

class DefaultFormRenderer extends FormRenderer
{
	private Form $form;

	public function __construct(Form $form)
	{
		$this->form = $form;
	}

	public function prepare(): void
	{
		$form = $this->form;

		$attributes = [
			new HtmlTagAttribute('method', ($form->isMethodPost() ? 'post' : 'get'), true),
			new HtmlTagAttribute('action', '?' . $form->getSentIndicator(), true),
		];
		$cssClasses = $form->getCssClasses();
		if (count($cssClasses) > 0) {
			$attributes[] = new HtmlTagAttribute('class', implode(' ', $cssClasses), true);
		}
		if ($form->acceptUpload()) {
			$attributes[] = new HtmlTagAttribute('enctype', 'multipart/form-data', true);
		}

		$htmlTag = new HtmlTag('form', false, $attributes);
		$htmlTag = $this->renderErrors($htmlTag);

		$defaultFormFieldRenderer = $form->getDefaultFormFieldRenderer();

		foreach ($form->getChildComponents() as $childComponent) {
			$componentRenderer = $childComponent->getRenderer();
			if (is_null($componentRenderer)) {

				if ($childComponent instanceof FormField) {
					$childComponentRenderer = new $defaultFormFieldRenderer($childComponent);
				} else {
					$childComponentRenderer = $childComponent->getDefaultRenderer();
				}
				$childComponent->setRenderer($childComponentRenderer);
			}
			$htmlTag->addTag($childComponent->getHtmlTag());
		}

		$this->setHtmlTag($htmlTag);
	}

	private function renderErrors(HtmlTag $htmlTag): HtmlTag
	{
		$form = $this->form;

		if (!$form->hasErrors(withChildElements: true)) {
			// This component and all parents/children don't have any errors
			return $htmlTag;
		}

		$errors = $form->getErrorsAsHtmlTextObjects();
		$amountOfErrors = count($errors);

		if ($amountOfErrors === 0) {
			// This component has no errors to render (parents/children still might have some, which we don't want to render here)
			return $htmlTag;
		}

		$mainAttributes = [new HtmlTagAttribute('class', 'form-error', true)];

		if ($amountOfErrors === 1) {
			$bTag = new HtmlTag('b', false);
			$bTag->addText(current($errors));
			$pTag = new HtmlTag('p', false, $mainAttributes);
			$pTag->addTag($bTag);
			$htmlTag->addTag($pTag);

			return $htmlTag;
		}

		// There are more than 1 errors, so we display ul-tag instead of p-tag
		$divTag = new HtmlTag('div', false, $mainAttributes);
		$ulTag = new HtmlTag('ul', false);
		foreach ($errors as $htmlText) {
			$liTag = new HtmlTag('li', false);
			$liTag->addText($htmlText);
			$ulTag->addTag($liTag);
		}
		$divTag->addTag($ulTag);
		$htmlTag->addTag($divTag);

		return $htmlTag;
	}
}