<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\common;

use DateTime;
use framework\common\SearchHelper;
use framework\core\Core;
use framework\security\CsrfToken;
use Throwable;

class filters
{
	private SearchHelper $searchHelper;
	private string $name;
	private Core $core;
	private array $chosenEnhancedDropdowns = [];
	private array $filters = [];

	public function __construct(Core $core, string $name)
	{
		$this->core = $core;
		$this->name = $name;
		$this->searchHelper = SearchHelper::getInstance($name, $core->getHttpRequest());
	}

	public function addFilter(string $name, string $label, string $type = 'text', string $colName = '', string $defaultValue = '', array $options = [], bool $primaryFilter = true): void
	{
		$this->filters[$name] = [
			'label'         => $label,
			'type'          => $type,
			'col'           => ($colName == '') ? $name : $colName,
			'value'         => $defaultValue,
			'options'       => $options,
			'primaryFilter' => $primaryFilter,
		];
	}

	public function setChosen(string $fieldName): void
	{
		if (!isset($this->filters[$fieldName])) {
			return;
		}

		$this->chosenEnhancedDropdowns[] = $fieldName;
	}

	public function getFilterData(string $name): array
	{
		return isset($this->filters[$name]) ? $this->filters[$name] : [];
	}

	public function check(): void
	{
		$searchHelper = $this->searchHelper;

		foreach ($this->filters as $filterName => $filterData) {
			switch ($filterData['type']) {
				case 'text':
					$this->filters[$filterName]['value'] = $searchHelper->checkString($filterName, $filterData['value']);
					break;

				case 'options':
					$this->filters[$filterName]['value'] = $searchHelper->checkFilter($filterData['options'], $filterName, $filterData['value']);
					break;

				case 'dateFrom':
				case 'dateTo':
					$this->filters[$filterName]['value'] = $this->checkDate($searchHelper->checkString($filterName, $filterData['value']));
					break;
			}
		}
	}

	/**
	 * Generates the HTML for the filters
	 *
	 * @param array $neutralValues : indexed array [fieldName => fieldValue, ...] containing definitions, which
	 *                             form values are considered as neutral values. Any non-neutral value causes
	 *                             the secondary filter row being shown, if that field is part of it
	 *
	 * @return string : HTML for display
	 * @todo Make more object oriented and check all data for rendering if it needs additional encoding
	 */
	public function display(array $neutralValues = []): string
	{
		$f = file_get_contents($this->core->getRequestHandler()->getAreaDir() . 'snippets/filters.html');

		$primary_filters = '';
		$secondary_filters = '';
		$secondaryFilterRowVisible = false;

		foreach ($this->filters as $filterName => $filterData) {
			$lc = ($filterData['value'] != '') ? ' class="highlight"' : '';
			$filterId = 'filter-' . $filterName;

			$filter = '';
			if (in_array($filterData['type'], ['text', 'dateFrom', 'dateTo'])) {
				$value = $filterData['value'];
				if (trim($value) != '' && ($filterData['type'] == 'dateFrom' || $filterData['type'] == 'dateTo')) {
					$dt = new DateTime($value);
					$value = $dt->format('d.m.Y H:i');
				}

				$filter = '<input type="text" class="text" name="' . $filterName . '" id="' . $filterId . '" value="' . str_replace('"', '&quot;', $value) . '">';
			} else if ($filterData['type'] == 'options') {
				if (in_array($filterName, $this->chosenEnhancedDropdowns)) {
					$filter = '<select name="' . $filterName . '" id="' . $filterId . '" class="chosen">';
				} else {
					$filter = '<select name="' . $filterName . '" id="' . $filterId . '">';
				}
				foreach ($filterData['options'] as $key => $val) {
					$selected = ($key == $filterData['value']) ? 'selected' : '';
					$filter .= '<option value="' . $key . '" ' . $selected . ' >' . $val['label'] . '</option>';
				}
				$filter .= '</select>';
			}

			if ($filterData['primaryFilter']) {
				$primary_filters .= '<li><label' . $lc . '>' . $filterData['label'] . $filter . '</label></li>';
			} else {
				$secondary_filters .= '<li><label' . $lc . '>' . $filterData['label'] . $filter . '</label></li>';
				// the "secondary filter row" should be shown, if any of the transmitted values there are NOT somehow "neutral":
				$fieldValue = trim($filterData['value']);
				if ($fieldValue !== '') {
					// check, if there is a additional definition of "neutral values":
					if (isset($neutralValues[$filterName])) {
						if ($neutralValues[$filterName] !== $fieldValue) {
							$secondaryFilterRowVisible = true; // because a value is not empty & neutral
						}
					} else {
						$secondaryFilterRowVisible = true; // because a value is not empty
					}
				}
			}
		}

		$secondaryFilterButton = empty($secondary_filters) ? '' : '<a class="trigger-table-filter-secondary[makeVisibleCss]" href="#">Erweiterte Suche</a>';

		$css = $secondaryFilterRowVisible ? ' triggered' : '';
		$html = str_replace('[primary_filter]', $primary_filters, $f);
		$html = str_replace('[secondary_filter_button]', $secondaryFilterButton, $html);
		$html = str_replace('[secondary_filter]', $secondary_filters, $html);
		$html = str_replace('[makeVisibleCss]', $css, $html);
		$html = str_replace('[csrfField]', CsrfToken::renderAsHiddenPostField(), $html);

		return $html;
	}

	public function getData(string $prefix = 'WHERE', string $suffix = ''): array
	{
		$condArr = [];
		$params = [];
		$searchHelper = $this->searchHelper;

		foreach ($this->filters as $filterData) {
			if (trim($filterData['value']) == '') {
				continue;
			}
			if ($filterData['type'] == 'text') {
				$sqlFilters = $searchHelper->createSQLFilters([$filterData['col'] => $filterData['value']]);
				$condArr[] = $sqlFilters['sql'];
				foreach ($sqlFilters['params'] as $param) {
					$params[] = $param;
				}
			} else if ($filterData['type'] == 'dateFrom' || $filterData['type'] == 'dateTo') {
				$x = ($filterData['type'] == 'dateFrom') ? '>=' : '<=';
				$condArr[] = $filterData['col'] . $x . '?';
				$params[] = $filterData['value'];
			} else if ($filterData['type'] == 'options') {
				if (isset($filterData['options'][$filterData['value']])) {
					$condArr[] = $filterData['options'][$filterData['value']]['cond'];
					foreach ($filterData['options'][$filterData['value']]['params'] as $param) {
						$params[] = $param;
					}
				}
			}
		}
		$cond = (count($condArr) > 0) ? ' ' . $prefix . ' ' . implode(' AND ', $condArr) . $suffix : '';

		return ['cond' => $cond, 'params' => $params];
	}

	private function checkDate($date): string
	{
		if (trim($date) == '') {
			return '';
		}
		try {
			$dateTime = new DateTime($date);
			$dtErrors = DateTime::getLastErrors();
			if ($dtErrors['warning_count'] > 0 || $dtErrors['error_count'] > 0) {
				return '';
			}

			return $dateTime->format('Y-m-d H:i:s');
		} catch (Throwable) {
			return '';
		}
	}
}