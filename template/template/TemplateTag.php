<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\template\template;

use Exception;

abstract class TemplateTag
{
	public function __construct()
	{
		if (($this instanceof TagNode) === false && ($this instanceof TagInline) === false) {
			throw new Exception('The class "' . get_class($this) . '" does not implement the class "TagNode" or "TagInline" and is so recognized as an illegal class for a custom tag."');
		}
	}
}