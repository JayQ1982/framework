<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component;

use framework\form\FormComponent;
use framework\form\FormRenderer;
use framework\form\renderer\FormInfoRenderer;
use framework\html\HtmlText;

class FormInfo extends FormComponent
{
	private HtmlText $title;
	private HtmlText $content;
	private string $dtClass;
	private string $ddClass;
	private bool $formInfoClass;

	public function __construct(HtmlText $title, HtmlText $content, string $dtClass = '', string $ddClass = '', bool $formInfoClass = false)
	{
		$this->title = $title;
		$this->content = $content;
		$this->dtClass = trim($dtClass);
		$this->ddClass = trim($ddClass);
		$this->formInfoClass = $formInfoClass;

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

	public function getDtClass(): string
	{
		return $this->dtClass;
	}

	public function getDdClass(): string
	{
		return $this->ddClass;
	}

	public function getFormInfoClass(): bool
	{
		return $this->formInfoClass;
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new FormInfoRenderer($this);
	}
}