<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\layout;

enum CheckboxOptionsLayout: int
{
	case NONE = 0;
	case DEFINITION_LIST = 1;
	case LEGEND_AND_LIST = 2;
	case CHECKBOX_ITEM = 3;
}