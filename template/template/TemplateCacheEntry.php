<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\template\template;

class TemplateCacheEntry
{
	public ?string $path = null;
	public ?int $changeTime = null;
	public ?int $size = null;
}
/* EOF */