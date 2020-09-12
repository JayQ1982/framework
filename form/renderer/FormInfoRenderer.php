<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\FormInfo;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

class FormInfoRenderer extends FormRenderer
{
	private FormInfo $formInfo;

	public function __construct(FormInfo $formInfo)
	{
		$this->formInfo = $formInfo;
	}

	public function prepare(): void
	{
		$formInfo = $this->formInfo;

		$dtTag = new FormTag('dt', false);
		$dtTag->addText(new FormText($formInfo->getTitle()));
		if ($formInfo->getDtClass() != '') {
			$dtTag->addFormTagAttribute(new FormTagAttribute('class', $formInfo->getDtClass()));
		}

		$ddTag = new FormTag('dd', false);
		$ddTag->addText(new FormText($formInfo->getContent(), $formInfo->isContentHTML()));
		if ($formInfo->getDdClass() != '') {
			$ddTag->addFormTagAttribute(new FormTagAttribute('class', $formInfo->getDdClass()));
		}

		if ($formInfo->getFormInfoClass()) {
			$dlClasses = ['form-info'];
		} else {
			$dlClasses = ['clearfix'];
		}
		$dlTag = new FormTag('dl', false, [new FormTagAttribute('class', implode(' ', $dlClasses))]);
		$dlTag->addTag($dtTag);
		$dlTag->addTag($ddTag);

		$this->setFormTag($dlTag);
	}
}
/* EOF */