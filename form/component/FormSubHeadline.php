<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component;

use framework\form\FormComponent;
use framework\form\FormTag;
use framework\form\FormText;

class FormSubHeadline extends FormComponent
{
	private int $headingLevel;
	private string $content;

	public function __construct(int $headingLevel, string $content)
	{
		$this->headingLevel = $headingLevel;
		$this->content = $content;

		parent::__construct(uniqid());
	}

	public function getFormTag(): FormTag
	{
		$headline = new FormTag('h' . $this->headingLevel, false, []);
		$headline->addText(new FormText($this->content));

		return $headline;
	}
}
/* EOF */