<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\FormControl;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

class FormControlRenderer extends FormRenderer
{
	private FormControl $formControl;

	public function __construct(FormControl $formControl)
	{
		$this->formControl = $formControl;
	}

	public function prepare(): void
	{
		$formControl = $this->formControl;

		$buttonTag = new FormTag('button', false, [
			new FormTagAttribute('type', 'submit'),
			new FormTagAttribute('name', $formControl->getName()),
		]);
		$buttonTag->addText(new FormText($formControl->getSubmitLabel()));

		$divTag = new FormTag('div', false, [new FormTagAttribute('class', 'form-control')]);
		$divTag->addTag($buttonTag);

		if (!is_null($formControl->getCancelLink())) {
			$aTag = new FormTag('a', false, [
				new FormTagAttribute('href', $formControl->getCancelLink()),
				new FormTagAttribute('class', 'link-cancel'),
			]);
			$aTag->addText(new FormText($formControl->getCancelLabel()));
			$divTag->addTag($aTag);
		}

		$this->setFormTag($divTag);
	}
}
/* EOF */