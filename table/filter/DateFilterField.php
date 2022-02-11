<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\table\filter;

use DateTime;
use framework\core\HttpRequest;
use framework\datacheck\Sanitizer;
use Throwable;

class DateFilterField extends AbstractTableFilterField
{
	private string $label;
	private ?DateTime $value = null;
	private string $dataTableColumnReference;
	private bool $mustBeLaterThan;

	public function __construct(string $identifier, string $label, string $dataTableColumnReference, bool $mustBeLaterThan)
	{
		parent::__construct(identifier: $identifier);
		$this->label = $label;
		$this->dataTableColumnReference = $dataTableColumnReference;
		$this->mustBeLaterThan = $mustBeLaterThan;
	}

	public function init(): void
	{
		$valueFromSession = $this->getFromSession($this->getIdentifier());
		if (!empty($valueFromSession)) {
			$this->value = new DateTime(datetime: $valueFromSession);
		}
	}

	public function reset(): void
	{
		$this->value = null;
		$this->saveToSession(index: $this->getIdentifier(), value: '');
	}

	public function checkInput(): void
	{
		$inputValue = Sanitizer::trimmedString(input: HttpRequest::getInputString(keyName: $this->getIdentifier()));

		if ($inputValue === '') {
			$this->reset();

			return;
		}

		try {
			$forceTimePart = '';
			if (!str_contains($inputValue, ':')) {
				$forceTimePart = $this->mustBeLaterThan ? ' 00:00:00' : ' 23:59:59';
			}

			$dateTimeObject = new DateTime(datetime: $inputValue . $forceTimePart);
			$dtErrors = DateTime::getLastErrors();
			if ($dtErrors['warning_count'] > 0 || $dtErrors['error_count'] > 0) {
				$this->reset();

				return;
			}
			$this->value = $dateTimeObject;
			$this->saveToSession(index: $this->getIdentifier(), value: $inputValue);
		} catch (Throwable) {
			$this->reset();
		}
	}

	public function getWhereConditions(): array
	{
		if (is_null(value: $this->value)) {
			return [];
		}

		if ($this->mustBeLaterThan) {
			return [$this->dataTableColumnReference . '>=?'];
		}

		return [$this->dataTableColumnReference . '<=?'];
	}

	public function getSqlParameters(): array
	{
		return is_null(value: $this->value) ? [] : [$this->value->format(format: 'Y-m-d H:i:s')];
	}

	protected function renderField(): string
	{
		$value = is_null(value: $this->value) ? '' : $this->value->format(format: 'd.m.Y H:i:s');

		return $this->label . '<input type="text" class="text" name="' . $this->getIdentifier() . '" id="filter-' . $this->getIdentifier() . '" value="' . $value . '">';
	}

	protected function highLightLabel(): bool
	{
		return !is_null(value: $this->value);
	}
}