<?php

namespace framework\vendor\libphonenumber;

class DefaultMetadataLoader implements MetadataLoaderInterface
{
	public function loadMetadata($metadataFileName)
	{
		return include $metadataFileName;
	}
}
