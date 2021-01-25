<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\form\listener;

use framework\form\component\collection\Form;
use framework\form\component\field\FileField;

abstract class FileFieldListener extends FormFieldListener
{
	public function onUploadSuccess(Form $parentForm, FileField $fileField, array $uploadedFileInfo): void { }

	public function onUploadFail(Form $parentForm, FileField $fileField, array $uploadedFileInfo): void { }
}