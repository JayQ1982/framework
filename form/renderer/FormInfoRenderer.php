<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\FormInfo;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;

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

		$dtTag = new HtmlTag('dt', false);
		$dtTag->addText($formInfo->getTitle());
		if ($formInfo->getDtClass() != '') {
			$dtTag->addHtmlTagAttribute(new HtmlTagAttribute('class', $formInfo->getDtClass(), true));
		}

		$ddTag = new HtmlTag('dd', false);
		$ddTag->addText($formInfo->getContent());
		if ($formInfo->getDdClass() != '') {
			$ddTag->addHtmlTagAttribute(new HtmlTagAttribute('class', $formInfo->getDdClass(), true));
		}

		if ($formInfo->getFormInfoClass()) {
			$dlClasses = ['form-info'];
		} else {
			$dlClasses = ['clearfix'];
		}
		$dlTag = new HtmlTag('dl', false, [new HtmlTagAttribute('class', implode(' ', $dlClasses), true)]);
		$dlTag->addTag($dtTag);
		$dlTag->addTag($ddTag);

		$this->setHtmlTag($dlTag);
	}
}