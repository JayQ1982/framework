<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component;

use framework\form\FormComponent;
use framework\form\FormRenderer;
use framework\form\renderer\FormInfoRenderer;

class FormInfo extends FormComponent
{
	private string $title;
	private ?string $content;
	private bool $contentIsHTML;
	private string $dtClass;
	private string $ddClass;
	private bool $formInfoClass;

	public function __construct(string $title, ?string $content, bool $contentIsHTML = false, string $dtClass = '', string $ddClass = '', bool $formInfoClass = false)
	{
		$this->title = $title;
		$this->content = trim($content);
		$this->contentIsHTML = $contentIsHTML;
		$this->dtClass = trim($dtClass);
		$this->ddClass = trim($ddClass);
		$this->formInfoClass = $formInfoClass;

		parent::__construct(uniqid());
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function isContentHTML(): bool
	{
		return $this->contentIsHTML;
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
/* EOF */