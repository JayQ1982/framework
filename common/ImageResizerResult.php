<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

enum ImageResizerResult
{
	case SUCCESS;
	case MISSING_SOURCE_IMAGE;
	case CREATE_ORIGINAL_FAILED;
	case CREATE_NEW_FAILED;
}