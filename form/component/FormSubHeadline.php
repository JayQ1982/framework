<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component;

use framework\form\FormComponent;
use framework\html\HtmlTag;
use framework\html\HtmlText;

class FormSubHeadline extends FormComponent
{
	private int $headingLevel;
	private HtmlText $content;

	public function __construct(int $headingLevel, HtmlText $content)
	{
		$this->headingLevel = $headingLevel;
		$this->content = $content;

		parent::__construct(uniqid());
	}

	public function getHtmlTag(): HtmlTag
	{
		$headline = new HtmlTag('h' . $this->headingLevel, false, []);
		$headline->addText($this->content);

		return $headline;
	}
}