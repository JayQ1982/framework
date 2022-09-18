<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

enum ImageResizerResult: int
{
	case SUCCESS = 1;
	case FAILED_TO_CREATE_DESTINATION_FILE = 2;
	case FAILED_TO_CREATE_NEW_IMAGE = 3;
}