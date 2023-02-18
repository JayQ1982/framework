<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component;

use framework\form\FormComponent;
use framework\form\FormRenderer;
use framework\form\renderer\FormInfoRenderer;
use framework\html\HtmlText;

class FormInfo extends FormComponent
{
	public function __construct(
		public readonly HtmlText $title,
		public readonly HtmlText $content,
		public readonly array    $dlClasses = [],
		public readonly array    $dtClasses = [],
		public readonly array    $ddClasses = []
	) {
		parent::__construct(uniqid());
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new FormInfoRenderer(formInfo: $this);
	}
}