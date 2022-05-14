<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\renderer;

use framework\form\component\field\FileField;
use framework\form\FormRenderer;
use framework\html\HtmlEncoder;
use framework\html\HtmlTag;
use framework\html\HtmlTagAttribute;
use framework\html\HtmlText;

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

		$wrapperClass = ($stillAllowedToUploadCount > 1 && $this->enhanceMultipleField) ? 'fileupload-enhanced' : 'fileupload';
		$wrapper = new HtmlTag('div', false, [
			new HtmlTagAttribute('class', $wrapperClass, true),
			new HtmlTagAttribute('data-max-files', $stillAllowedToUploadCount, true),
		]);

		if (!empty($alreadyUploadedFiles)) {
			$fileListBox = new HtmlTag('ul', false, [
				new HtmlTagAttribute('class', 'list-fileupload', true),
			]);
			$htmlContent = '';
			foreach ($alreadyUploadedFiles as $hash => $fileDataModel) {
				$htmlContent .= '<li><b>' . HtmlEncoder::encode(value: $fileDataModel->getName()) . '</b> <button type="submit" name="' . FileField::FIELD_PREFIX . '_removeAttachment" value="' . HtmlEncoder::encode(value: $hash) . '">löschen</button>';
			}
			$fileListBox->addText(HtmlText::encoded($htmlContent));
			$wrapper->addTag($fileListBox);
		}

		$inputTag = new HtmlTag('input', true);
		$inputTag->addHtmlTagAttribute(new HtmlTagAttribute('type', 'file', true));
		$inputTag->addHtmlTagAttribute(new HtmlTagAttribute('name', $fileField->getName() . '[]', true));
		$inputTag->addHtmlTagAttribute(new HtmlTagAttribute('id', $fileField->getId(), true));
		if ($stillAllowedToUploadCount > 1) {
			$inputTag->addHtmlTagAttribute(new HtmlTagAttribute('multiple', null, true));
		}
		FormRenderer::addAriaAttributesToHtmlTag($fileField, $inputTag);
		$wrapper->addTag($inputTag);

		// Add the fileStore-Pointer-ID for the SESSION as hidden field
		$wrapper->addTag(new HtmlTag('input', true, [
			new HtmlTagAttribute('type', 'hidden', true),
			new HtmlTagAttribute('name', FileField::FIELD_PREFIX, true),
			new HtmlTagAttribute('value', $fileField->getUniqueSessFileStorePointer(), true),
		]));

		$this->setHtmlTag($wrapper);
	}
}