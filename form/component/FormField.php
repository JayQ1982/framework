<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component;

use ArrayObject;
use DateTime;
use framework\form\component\collection\Form;
use framework\form\FormComponent;
use framework\form\FormRenderer;
use framework\form\FormRule;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;
use framework\form\listener\FormFieldListener;
use UnexpectedValueException;

abstract class FormField extends FormComponent
{
	private string $id;
	private string $label;
	/** @var mixed */
	private $value;
	private $originalValue;
	/** @var FormRule[] */
	private array $rules = [];
	/** @var FormFieldListener[] */
	protected array $listeners = [];
	protected ?string $fieldInfoAsHTML = null;
	protected string $labelInfoText = '';
	protected ?string $additionalColumnContent = null;
	protected Form $topFormComponent;
	private array $cssClasses = [];

	// Renderer options:
	protected bool $renderRequiredAbbr = true;
	private bool $renderLabel = true;
	private bool $acceptArrayAsValue = false;

	/**
	 * @param string $name          : The internal name for this formField which is also used by the renderer (name="")
	 * @param string $label         : The field label to be used by the renderer
	 * @param mixed  $value         : The original value for this formField. Depending on the specific field it can be a string, float, integer and even an
	 *                              array (as in NameserverField). By default it is null.
	 * @param string $labelInfoText : Additional text padded to the displayed label-name (see FileField max-Info for example)
	 */
	public function __construct(string $name, string $label, $value = null, string $labelInfoText = '')
	{
		$this->id = $name;
		$this->label = $label;
		parent::__construct($name);

		if (is_array($value)) {
			// If the value to be pre-filled is already an array we can also accept an array as user input
			$this->acceptArrayAsValue();
		}

		$this->setValue($value);
		$this->setOriginalValue($value);
		$this->labelInfoText = $labelInfoText;
	}

	public function addCssClass(string $className): void
	{
		$this->cssClasses[] = $className;
	}

	public function getCssClasses(): array
	{
		return $this->cssClasses;
	}

	protected function acceptArrayAsValue(): void
	{
		$this->acceptArrayAsValue = true;
	}

	protected function isArrayAsValueAllowed(): bool
	{
		return $this->acceptArrayAsValue;
	}

	public function setTopFormComponent(Form $topFormComponent): void
	{
		$this->topFormComponent = $topFormComponent;
	}

	public function setId(string $id): void
	{
		$this->id = $id;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function setFieldInfoAsHTML(?string $fieldInfoAsHTML): void
	{
		$this->fieldInfoAsHTML = $fieldInfoAsHTML;
	}

	public function getFieldInfoAsHTML(): ?string
	{
		return $this->fieldInfoAsHTML;
	}

	/**
	 * @param string|null $content : This content will be properly HTML-encoded later!
	 */
	public function setAdditionalColumnContent(?string $content): void
	{
		$this->additionalColumnContent = $content;
	}

	public function getAdditionalColumnContent(): ?string
	{
		return $this->additionalColumnContent;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function getLabelInfoText(): string
	{
		return $this->labelInfoText;
	}

	public function setLabelInfoText(string $labelInfoText): void
	{
		$this->labelInfoText = $labelInfoText;
	}

	public function setValue($value): void
	{
		if (is_array($value) && !$this->isArrayAsValueAllowed()) {
			$this->addError('Die ungültige Eingabe wurde ignoriert.');

			return;
		}

		$this->value = $value;
	}

	public function getRawValue(bool $returnNullIfEmpty = false)
	{
		if ($this->isValueEmpty() && $returnNullIfEmpty) {
			return null;
		}

		return $this->value;
	}

	public function renderValue(): string
	{
		return FormRenderer::htmlEncode($this->getRawValue());
	}

	public function getOriginalValue()
	{
		return $this->originalValue;
	}

	public function getAddedValues(): array
	{
		if (!is_array($this->value) || !is_array($this->originalValue)) {
			return [];
		}

		$addedValues = [];

		foreach ($this->value as $selectedValue) {
			if (!in_array($selectedValue, $this->originalValue)) {
				$addedValues[] = $selectedValue;
			}
		}

		return $addedValues;
	}

	public function getRemovedValues(): array
	{
		if (!is_array($this->value) || !is_array($this->originalValue)) {
			return [];
		}

		$removedValues = [];

		foreach ($this->originalValue as $originalValue) {
			if (!in_array($originalValue, $this->value)) {
				$removedValues[] = $originalValue;
			}
		}

		return $removedValues;
	}

	public function setOriginalValue($value): void
	{
		$this->originalValue = $value;
	}

	public function addRule(FormRule $formRule): void
	{
		$this->rules[] = $formRule;
	}

	protected function hasRule(string $ruleClassName): bool
	{
		foreach ($this->rules as $r) {
			if (get_class($r) === $ruleClassName) {
				return true;
			}
		}

		return false;
	}

	public function isRequired(): bool
	{
		return $this->hasRule('framework\form\rule\RequiredRule');
	}

	public function addListener(FormFieldListener $formFieldListener): void
	{
		$this->listeners[] = $formFieldListener;
	}

	public function isValueEmpty(): bool
	{
		if ($this->value === null) {
			return true;
		}

		if (is_scalar($this->value)) {
			return (strlen(trim($this->value)) <= 0);
		} else if (is_array($this->value)) {
			return (count(array_filter($this->value)) <= 0);
		} else if ($this->value instanceof ArrayObject) {
			return (count(array_filter((array)$this->value)) <= 0);
		} else if ($this->value instanceof DateTime) {
			return false;
		} else {
			throw new UnexpectedValueException('Could not check value against emptiness');
		}
	}

	/**
	 * Use the rules to validate the input data.
	 *
	 * @param array $inputData      : All input data
	 * @param bool  $overwriteValue : Overwrite current value by value from inputData (true by default)
	 *
	 * @return bool : Validation result (false on error)
	 */
	public function validate(array $inputData, bool $overwriteValue = true): bool
	{
		if ($overwriteValue) {
			$defaultValue = $this->isArrayAsValueAllowed() ? [] : null;
			$this->setValue(array_key_exists($this->getName(), $inputData) ? $inputData[$this->getName()] : $defaultValue);
		}

		foreach ($this->listeners as $formFieldListener) {
			if ($this->isValueEmpty()) {
				$formFieldListener->onEmptyValueBeforeValidation($this->topFormComponent, $this);
			} else {
				$formFieldListener->onNotEmptyValueBeforeValidation($this->topFormComponent, $this);
			}
		}

		foreach ($this->rules as $formRule) {
			if (!$formRule->validate($this)) {
				$this->addError($formRule->getErrorMessage());
			}
		}

		$hasErrors = $this->hasErrors();
		foreach ($this->listeners as $formFieldListener) {
			if ($this->isValueEmpty()) {
				$formFieldListener->onEmptyValueAfterValidation($this->topFormComponent, $this);
			} else {
				$formFieldListener->onNotEmptyValueAfterValidation($this->topFormComponent, $this);
			}

			if ($hasErrors) {
				$formFieldListener->onValidationError($this->topFormComponent, $this);
			} else {
				$formFieldListener->onValidationSuccess($this->topFormComponent, $this);
			}
		}

		return !$this->hasErrors();
	}

	public function addFieldInfo(FormTag $formTag): FormTag
	{
		$divTag = new FormTag('div', false, [
			new FormTagAttribute('class', 'form-input-info'),
			new FormTagAttribute('id', $this->getName() . '-info'),
		]);
		$divTag->addText(new FormText($this->fieldInfoAsHTML, true));
		$formTag->addTag($divTag);

		return $formTag;
	}

	public function setRenderRequiredAbbr(bool $renderRequiredAbbr): void
	{
		$this->renderRequiredAbbr = $renderRequiredAbbr;
	}

	/**
	 * Suppresses the VISIBLE label rendering of associated input field
	 * (It will be still readable by screen readers)
	 */
	public function setRenderLabelFalse(): void
	{
		$this->renderLabel = false;
	}

	public function isRenderLabel(): bool
	{
		return $this->renderLabel;
	}

	public function isRenderRequiredAbbr(): bool
	{
		return $this->renderRequiredAbbr;
	}

	/**
	 * Returns whether the original value has changed (true) or not (false)
	 *
	 * @return bool
	 */
	public function valueHasChanged(): bool
	{
		return ($this->value !== $this->originalValue);
	}
}
/* EOF */