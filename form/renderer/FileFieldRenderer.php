<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\renderer;

use framework\form\component\field\FileField;
use framework\form\FormRenderer;
use framework\form\FormTag;
use framework\form\FormTagAttribute;
use framework\form\FormText;

class FileFieldRenderer extends FormRenderer
{
	private FileField $fileField;
	private bool $enhanceMultipleField = true;

	/**
	 * @param FileField $fileField
	 */
	public function __construct(FileField $fileField)
	{
		$this->fileField = $fileField;
	}

	public function setEnhanceMultipleField(bool $enhanceMultipleField)
	{
		$this->enhanceMultipleField = $enhanceMultipleField;
	}

	public function prepare(): void
	{
		$fileField = $this->fileField;

		$alreadyUploadedFiles = $fileField->getFiles();

		$stillAllowedToUploadCount = $fileField->getMaxFileUploadCount() - count($alreadyUploadedFiles);
		if ($stillAllowedToUploadCount < 0) {
			$stillAllowedToUploadCount = 0;
		}

		$attributes = [
			new FormTagAttribute('type', 'file'),
			new FormTagAttribute('name', $fileField->getName() . '[]'),
			new FormTagAttribute('id', $fileField->getId()),
		];

		$wrapperClass = 'fileupload';
		if ($stillAllowedToUploadCount > 1) {
			$attributes[] = new FormTagAttribute('multiple', null);

			if ($this->enhanceMultipleField) {
				$wrapperClass = 'fileupload-enhanced';
			}
		}

		$ariaDescribedBy = [];

		if ($fileField->hasErrors()) {
			$attributes[] = new FormTagAttribute('aria-invalid', 'true');
			$ariaDescribedBy[] = $fileField->getName() . '-error';
		}

		if (!is_null($fileField->getFieldInfoAsHTML())) {
			$ariaDescribedBy[] = $fileField->getName() . '-info';
		}

		if (count($ariaDescribedBy) > 0) {
			$attributes[] = new FormTagAttribute('aria-describedby', implode(' ', $ariaDescribedBy));
		}

		$wrapper = new FormTag('div', false, [
			new FormTagAttribute('class', $wrapperClass),
			new FormTagAttribute('data-max-files', $stillAllowedToUploadCount),
		]);

		if (!empty($alreadyUploadedFiles)) {
			$fileListBox = new FormTag('ul', false, [
				new FormTagAttribute('class', 'list-fileupload'),
			]);
			$htmlContent = '';
			foreach ($alreadyUploadedFiles as $hash => $fileData) {
				$htmlContent .= '<li class="clearfix"><b>' . FormRenderer::htmlEncode($fileData->getName()) . '</b> <button type="submit" name="' . FileField::PREFIX . '_removeAttachment" value="' . FormRenderer::htmlEncode($hash) . '">löschen</button>';
			}
			$fileListBox->addText(new FormText($htmlContent, true));
			$wrapper->addTag($fileListBox);
		}

		$wrapper->addTag(new FormTag('input', true, $attributes));

		// Add the fileStore-Pointer-ID for the SESSION as hidden field
		$wrapper->addTag(new FormTag('input', true, [
			new FormTagAttribute('type', 'hidden'),
			new FormTagAttribute('name', FileField::PREFIX),
			new FormTagAttribute('value', $fileField->getUniqueSessFileStorePointer()),
		]));

		$this->setFormTag($wrapper);
	}
}
/* EOF */
