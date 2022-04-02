<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\renderer;

use framework\form\component\FormControl;
use framework\form\FormRenderer;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;

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

		$buttonTag = new HtmlTag('button', false, [
			new HtmlTagAttribute('type', 'submit', true),
			new HtmlTagAttribute('name', $formControl->getName(), true),
		]);
		$buttonTag->addText($formControl->getSubmitLabel());

		$divTag = new HtmlTag('div', false, [new HtmlTagAttribute('class', 'form-control', true)]);
		$divTag->addTag($buttonTag);

		if (!is_null($formControl->getCancelLink())) {
			$aTag = new HtmlTag('a', false, [
				new HtmlTagAttribute('href', $formControl->getCancelLink(), true),
				new HtmlTagAttribute('class', 'link-cancel', true),
			]);
			$aTag->addText($formControl->getCancelLabel());
			$divTag->addTag($aTag);
		}

		$this->setHtmlTag($divTag);
	}
}