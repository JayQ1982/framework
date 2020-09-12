<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\collection\Form;
use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

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
			new FormTagAttribute('method', ($form->isMethodPost() ? 'post' : 'get')),
			new FormTagAttribute('action', '?' . $form->getSentVar()),
		];
		$cssClasses = $form->getCssClasses();
		if (count($cssClasses) > 0) {
			$attributes[] = new FormTagAttribute('class', implode(' ', $cssClasses));
		}
		if ($form->acceptUpload()) {
			$attributes[] = new FormTagAttribute('enctype', 'multipart/form-data');
		}

		$formTag = new FormTag('form', false, $attributes);
		$formTag = $this->renderErrors($formTag);

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
			$formTag->addTag($childComponent->getFormTag());
		}

		$this->setFormTag($formTag);
	}

	private function renderErrors(FormTag $formTag): FormTag
	{
		$form = $this->form;

		if (!$form->hasErrors()) {
			// This component and all parents/children don't have any errors
			return $formTag;
		}

		// This is a render-function. Therefore we need the errors as HTML in any case.
		$errorsHTMLencoded = ($form->hasHTMLencodedErrors() ? $form->getErrors(true) : FormRenderer::htmlEncode($form->getErrors(false)));

		$amountOfErrors = count($errorsHTMLencoded);

		if ($amountOfErrors === 0) {
			// This component has no errors to render (parents/children still might have some, which we don't want to render here)
			return $formTag;
		}

		$mainAttributes = [
			new FormTagAttribute('class', 'form-error'),
		];

		if ($amountOfErrors === 1) {
			$bTag = new FormTag('b', false);
			$bTag->addText(new FormText($errorsHTMLencoded[0], true));
			$pTag = new FormTag('p', false, $mainAttributes);
			$pTag->addTag($bTag);
			$formTag->addTag($pTag);

			return $formTag;
		}

		// There are more than 1 errors, so we display ul-tag instead of p-tag
		$divTag = new FormTag('div', false, $mainAttributes);
		$ulTag = new FormTag('ul', false);
		foreach ($errorsHTMLencoded as $error) {
			$liTag = new FormTag('li', false);
			$liTag->addText(new FormText($error, true));
			$ulTag->addTag($liTag);
		}
		$divTag->addTag($ulTag);
		$formTag->addTag($divTag);

		return $formTag;
	}
}
/* EOF */