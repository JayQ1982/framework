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
		private HtmlText $title,
		private HtmlText $content,
		private array    $dlClasses = [],
		private array    $dtClasses = [],
		private array    $ddClasses = []
	) {
		parent::__construct(uniqid());
	}

	public function getTitle(): HtmlText
	{
		return $this->title;
	}

	public function getContent(): HtmlText
	{
		return $this->content;
	}

	public function getDlClasses(): array
	{
		return $this->dlClasses;
	}

	public function getDtClasses(): array
	{
		return $this->dtClasses;
	}

	public function getDdClasses(): array
	{
		return $this->ddClasses;
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new FormInfoRenderer($this);
	}
}