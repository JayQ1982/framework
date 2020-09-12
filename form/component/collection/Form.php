<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\collection;

use Exception;
use LogicException;
use framework\form\component\CSRFtoken;
use framework\form\component\field\CSRFtokenField;
use framework\form\component\FormField;
use framework\form\FormCollection;
use framework\form\FormComponent;
use framework\form\FormRenderer;
use framework\form\renderer\DefaultFormRenderer;
use framework\form\rule\ValidCSRFtokenValue;

class Form extends FormCollection
{
	private bool $acceptUpload;
	private ?string $globalErrorMessageHTML;
	private bool $methodPost;
	private string $sentVar;
	private array $cssClasses = [];
	private bool $renderRequiredAbbr = true;
	private string $defaultFormFieldRenderer = '\framework\form\renderer\DefinitionListRenderer';

	public function __construct(string $name, bool $acceptUpload = false, ?string $globalErrorMessageHTML = null, bool $methodPost = true, string $sentVar = 'send')
	{
		$this->acceptUpload = $acceptUpload;
		$this->globalErrorMessageHTML = $globalErrorMessageHTML;
		$this->methodPost = $methodPost;
		$this->sentVar = $sentVar;

		parent::__construct($name);

		$this->addField(new CSRFtokenField());
	}

	public function removeCsrfProtection(): void
	{
		if ($this->hasChildComponent(CSRFtoken::getFieldName())) {
			$this->removeChildComponent(CSRFtoken::getFieldName());
		}
	}

	public function setDefaultFormFieldRenderer(string $rendererName): void
	{
		$this->defaultFormFieldRenderer = $rendererName;
	}

	public function getDefaultFormFieldRenderer(): string
	{
		return $this->defaultFormFieldRenderer;
	}

	public function addCssClass(string $className): void
	{
		$this->cssClasses[] = $className;
	}

	public function addComponent(FormComponent $formComponent): void
	{
		$this->addChildComponent($formComponent);
	}

	public function addField(FormField $formField): void
	{
		if (!$this->renderRequiredAbbr) {
			$formField->setRenderRequiredAbbr(false);
		}
		$formField->setTopFormComponent($this);
		$this->addChildComponent($formField);
	}

	/**
	 * @param FormField[] $fields
	 */
	public function addFields(array $fields): void
	{
		foreach ($fields as $formField) {
			$this->addField($formField);
		}
	}

	public function addAssemblage(array $fieldsAndComponents): void
	{
		foreach ($fieldsAndComponents as $segment) {
			if ($segment instanceof FormField) {
				$this->addField($segment);
				continue;
			}
			if ($segment instanceof FormComponent) {
				$this->addComponent($segment);
				continue;
			}
			throw new LogicException('Neither an instance of FormField nor FormComponent.');
		}
	}

	public function hasField(string $name): bool
	{
		if (!$this->hasChildComponent($name)) {
			return false;
		}

		$component = $this->getChildComponent($name);

		return ($component instanceof FormField);
	}

	/**
	 * @param string $name
	 *
	 * @return FormField|FormComponent
	 */
	public function getField(string $name): FormField
	{
		if (!$this->hasField($name)) {
			throw new Exception('The requested component ' . $name . ' is not an instance of FormField');
		}

		return $this->getChildComponent($name);
	}

	public function removeField(string $name): void
	{
		if (!$this->hasField($name)) {
			throw new Exception('The requested component ' . $name . ' is not an instance of FormField');
		}
		$this->removeChildComponent($name);
	}

	public function isSent(): bool
	{
		return array_key_exists($this->sentVar, $_REQUEST);
	}

	public function validate(): bool
	{
		if (!$this->isSent()) {
			return false;
		}

		$inputData = ($this->methodPost ? $_POST : $_GET) + $_FILES;

		foreach ($this->getChildComponents() as $formComponent) {

			if (!$formComponent instanceof FormField) {
				continue;
			}

			$formComponent->validate($inputData);
		}

		if (!$this->hasErrors()) {
			$this->validateCSRF($inputData);
		}

		if ($this->hasErrors() && empty($this->getErrors($this->hasHTMLencodedErrors())) && !is_null($this->globalErrorMessageHTML)) {
			$this->addError($this->globalErrorMessageHTML, true);
		}

		return !$this->hasErrors();
	}

	private function validateCSRF(array $inputData): void
	{
		if (!$this->hasChildComponent(CSRFtoken::getFieldName())) {
			// The CSRF protection has been disabled
			return;
		}

		/** @var CSRFtokenField $csrfTokenField */
		$csrfTokenField = $this->getField(CSRFtoken::getFieldName());

		$validCSRFtokenValue = new ValidCSRFtokenValue();
		$csrfTokenField->addRule($validCSRFtokenValue);

		if (!$csrfTokenField->validate($inputData)) {
			$this->addError($validCSRFtokenValue->getErrorMessage());
		}
	}

	/**
	 * @return FormField[]
	 */
	public function getAllFields(): array
	{
		$allFields = [];

		foreach ($this->getChildComponents() as $formComponent) {
			if (!$formComponent instanceof FormField) {
				continue;
			}
			$allFields[] = $formComponent;
		}

		return $allFields;
	}

	public function dontRenderRequiredAbbr(): void
	{
		$this->renderRequiredAbbr = false;
	}

	public function isMethodPost(): bool
	{
		return $this->methodPost;
	}

	public function getSentVar(): string
	{
		return $this->sentVar;
	}

	public function getCssClasses(): array
	{
		return $this->cssClasses;
	}

	public function acceptUpload(): bool
	{
		return $this->acceptUpload;
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new DefaultFormRenderer($this);
	}

	public function renderErrors(): string
	{
		if (!$this->hasErrors()) {
			return '';
		}

		$errors = $this->getErrors();
		foreach ($this->getAllFields() as $thisField) {
			foreach ($thisField->getErrors() as $error) {
				$errors[] = $error;
			}
		}

		return implode('<br>', $errors);
	}

}
/* EOF */