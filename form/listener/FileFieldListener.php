<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\listener;

use framework\form\component\collection\Form;
use framework\form\component\field\FileField;

abstract class FileFieldListener extends FormFieldListener
{
	/**
	 * Gets executed if the file(s) was/were uploaded successfully
	 *
	 * @param Form      $form     The parent Form component
	 * @param FileField $field    The FileField instance
	 * @param array     $fileInfo Information about the uploaded file
	 */
	public function onUploadSuccess(Form $form, FileField $field, array $fileInfo)
	{

	}

	/**
	 * Gets executed if the file upload(s) failed
	 *
	 * @param Form      $form     The parent Form component
	 * @param FileField $file     The FileField instance
	 * @param array     $fileInfo Information about the uploaded file
	 */
	public function onUploadFail(Form $form, FileField $file, array $fileInfo)
	{

	}
}
/* EOF */