<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\component\field;

use DirectoryIterator;
use framework\datacheck\Sanitizer;
use framework\form\component\FormField;
use framework\form\FormRenderer;
use framework\form\listener\FileFieldListener;
use framework\form\model\FileDataModel;
use framework\form\renderer\FileFieldRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlEncoder;
use framework\html\HtmlText;

class FileField extends FormField
{
	public const FIELD_PREFIX = 'form_fileField';

	public const VALUE_NAME = 'name';
	public const VALUE_TMP_NAME = 'tmp_name';
	public const VALUE_TYPE = 'type';
	public const VALUE_ERROR = 'error';
	public const VALUE_SIZE = 'size';

	// Hint: We need searchable Strings outside this class, therefore please do NOT insert dynamic Strings into them:
	public const ERRMSG_FILE_EMPTY = 'Die Datei war leer: ';
	public const ERRMSG_FILE_INCOMPLETE = 'Die Datei wurde unvollständig hochgeladen: ';
	public const ERRMSG_FILE_TOO_BIG = 'Die Datei war zu gross: ';
	public const ERRMSG_FILE_TECHERROR = 'Es ist ein technischer Fehler beim Hochladen der Datei aufgetreten: ';

	private int $maxFileUploadCount;
	private string $uniqueSessFileStorePointer;
	private HtmlText $tooManyFilesErrMsg;
	private ?string $deleteFileHash = null;

	/**
	 * @param string        $name
	 * @param HtmlText      $label
	 * @param HtmlText|null $requiredError      : NULL, if file upload is not required, otherwise the error message if no file was uploaded
	 * @param int           $maxFileUploadCount : Maximal amount of allowed files (1 by default) with that field
	 * @param ?HtmlText     $tooManyFilesErrMsg : Individual error message if more than allowed amount of files are uploaded. Placeholder [max] will be replaced
	 *                                          by the max amount.
	 */
	public function __construct(string $name, HtmlText $label, ?HtmlText $requiredError = null, int $maxFileUploadCount = 1, ?HtmlText $tooManyFilesErrMsg = null)
	{
		if ($maxFileUploadCount < 1) {
			$maxFileUploadCount = 1; // silent correction
		}
		$this->maxFileUploadCount = $maxFileUploadCount;
		$this->uniqueSessFileStorePointer = $this->sanitizeUniqueID(uniqid(date('ymdHis') . '__', true));
		$this->tooManyFilesErrMsg = is_null($tooManyFilesErrMsg) ? HtmlText::encoded('Nur [max] Datei(en) möglich.') : $tooManyFilesErrMsg;

		// To always handle value internally as array we force an empty array on initialization
		parent::__construct(
			name: $name,
			label: $label,
			value: [],
			labelInfoText: HtmlText::encoded('(max. ' . $maxFileUploadCount . ')')
		);

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}
	}

	private function sanitizeUniqueID(string $uid): string
	{
		// We do not allow dangerous characters in the pointer, as it will become part of
		//   an filesystem path; And we want to easily detect these later in the external input:
		return preg_replace(
			pattern: '/[^a-z\d_]/',
			replacement: '',
			subject: $uid
		);
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new FileFieldRenderer($this);
	}

	/**
	 * @param array $inputData      : Raw inputData
	 * @param bool  $overwriteValue : Overwrite current value by value from inputData (true by default)
	 *
	 * @return bool
	 */
	public function validate(array $inputData, bool $overwriteValue = true): bool
	{
		if ($overwriteValue) {
			// Remove all temporary files older than 2 days
			$this->removeOldFiles();

			// The following two checks must be done before parent::validate() to have the required data available
			if (isset($inputData[FileField::FIELD_PREFIX]) && is_scalar($inputData[FileField::FIELD_PREFIX])) {
				$receivedUid = Sanitizer::trimmedString($inputData[FileField::FIELD_PREFIX]);
				// If that value is tampered by a "black hat hacker", he should just grab securely into an "empty bowl".
				//   Therefore we look for only allowed characters given in sanitizeUniqueID():
				$cleanedUid = $this->sanitizeUniqueID($receivedUid);
				if ($receivedUid === $cleanedUid) {
					// ONLY THEN take it:
					$this->uniqueSessFileStorePointer = $cleanedUid;
				}
			}

			if (isset($inputData[FileField::FIELD_PREFIX . '_removeAttachment']) && is_scalar($inputData[FileField::FIELD_PREFIX . '_removeAttachment'])) {
				// Referenced usage at FileFieldRenderer::prepare()
				$this->deleteFileHash = Sanitizer::trimmedString($inputData[FileField::FIELD_PREFIX . '_removeAttachment']);
			}
		}

		// parent::validate() does already correctly trigger $this->setValue() which is overwritten below
		parent::validate($inputData, $overwriteValue);

		// Trigger additional file field specific listeners
		foreach ($this->listeners as $formFieldListener) {
			if (!($formFieldListener instanceof FileFieldListener)) {
				continue;
			}

			$value = $this->getRawValue();
			foreach ($value as $fileInfo) {
				if ($fileInfo[FileField::VALUE_ERROR] === UPLOAD_ERR_OK) {
					$formFieldListener->onUploadSuccess($this->topFormComponent, $this, $fileInfo);
				} else {
					$formFieldListener->onUploadFail($this->topFormComponent, $this, $fileInfo);
				}
			}
		}

		return !$this->hasErrors(withChildElements: true);
	}

	/**
	 * Restructures an input array of multiple files
	 *
	 * @param array $filesArr
	 *
	 * @return FileDataModel[]
	 */
	protected function convertMultiFileArray(array $filesArr): array
	{
		$files = [];
		$filesCount = count($filesArr[FileField::VALUE_NAME]);

		for ($i = 0; $i < $filesCount; ++$i) {
			if ($filesArr[FileField::VALUE_ERROR][$i] === UPLOAD_ERR_NO_FILE) {
				// This represents "no files uploaded"
				continue;
			}

			$fileDataModel = new FileDataModel(
				Sanitizer::trimmedString($filesArr[FileField::VALUE_NAME][$i]),
				Sanitizer::trimmedString($filesArr[FileField::VALUE_TMP_NAME][$i]),
				Sanitizer::trimmedString($filesArr[FileField::VALUE_TYPE][$i]),
				(int)$filesArr[FileField::VALUE_ERROR][$i],
				(int)$filesArr[FileField::VALUE_SIZE][$i]
			);

			$files[] = $fileDataModel;
		}

		return $files;
	}

	/**
	 * Get an array with all already uploaded files. Automatically removes files not existing (anymore) in file system.
	 *
	 * @return FileDataModel[]
	 */
	private function getAlreadyUploadedFiles(): array
	{
		$usfsp = $this->getUniqueSessFileStorePointer();
		if (!isset($_SESSION[$usfsp])) {
			return $_SESSION[$usfsp] = [];
		}

		/** @var FileDataModel $fileDataModel */
		foreach ($_SESSION[$usfsp] as $hash => $fileDataModel) {
			if (!file_exists($fileDataModel->getTmpName())) {
				unset($_SESSION[$usfsp][$hash]);
			}
		}

		return $_SESSION[$usfsp];
	}

	/**
	 * @param null|array $value : Array with additional (uploaded) files to be added
	 */
	public function setValue($value = []): void
	{
		// Always respect already uploaded files when (re)setting the value
		$fileArray = $this->getAlreadyUploadedFiles();

		// Remove an already uploaded file, if requested
		if (!is_null($this->deleteFileHash) && isset($fileArray[$this->deleteFileHash])) {

			if (file_exists($fileArray[$this->deleteFileHash]->getTmpName())) {
				unlink($fileArray[$this->deleteFileHash]->getTmpName());
			}

			unset($fileArray[$this->deleteFileHash]);
		}

		// Add new (uploaded) files to fileArray
		if (is_array($value)) {
			$fileArray = $this->addFilesFromDataArray($fileArray, $value);
		}

		// Store new fileArray to session and current field value
		parent::setValue($_SESSION[$this->getUniqueSessFileStorePointer()] = $fileArray);
	}

	private function addFilesFromDataArray(array $originalFileArray, array $addFileArray): array
	{
		// Check if the data is available in the expected form
		if (
			!isset($addFileArray[FileField::VALUE_NAME])
			|| !isset($addFileArray[FileField::VALUE_TMP_NAME])
			|| !isset($addFileArray[FileField::VALUE_TYPE])
			|| !isset($addFileArray[FileField::VALUE_ERROR])
			|| !isset($addFileArray[FileField::VALUE_SIZE])
		) {
			return $originalFileArray;
		}

		// Convert input data into an array of fileData objects
		$convertedMultiFileArray = $this->convertMultiFileArray($addFileArray);

		// If new amount of files exceeds the limit, we add error and return the originalFileArray
		if ((count($originalFileArray) + count($convertedMultiFileArray)) > $this->maxFileUploadCount) {
			$this->addError(str_replace('[max]', $this->maxFileUploadCount, $this->tooManyFilesErrMsg->render()), true);

			return $originalFileArray;
		}

		$newFileArray = $originalFileArray;

		foreach ($convertedMultiFileArray as $fileDataModel) {
			$encodedFileName = HtmlEncoder::encode(value: $fileDataModel->getName());
			// If upload was okay:
			if ($fileDataModel->getError() === UPLOAD_ERR_OK) {
				// Special case from LIVE/PROD:
				if ($fileDataModel->getSize() === 0) {
					$this->addError(FileField::ERRMSG_FILE_EMPTY . $encodedFileName, true);
					continue;
				}
				$fileDataModel = $this->saveNewFile($fileDataModel);
				// Usage of sha1 is save here
				$hash = sha1($fileDataModel->getTmpName());
				$newFileArray[$hash] = $fileDataModel;
				continue;
			}
			// Anything other are errors
			switch ($fileDataModel->getError()) {
				case UPLOAD_ERR_INI_SIZE : // fallthrough
				case UPLOAD_ERR_FORM_SIZE:
					$this->addError(FileField::ERRMSG_FILE_TOO_BIG . $encodedFileName, true);
					break;
				case UPLOAD_ERR_PARTIAL:
					$this->addError(FileField::ERRMSG_FILE_INCOMPLETE . $encodedFileName, true);
					break;
				case UPLOAD_ERR_NO_FILE:
					// Silently ignore
					break;
				default:
					$this->addError(FileField::ERRMSG_FILE_TECHERROR . $encodedFileName, true);
					break;
			}
		}

		return $newFileArray;
	}

	/**
	 * Returns the path to the root directory to store the temporary files
	 * If directory does not exist, it will be created
	 *
	 * @return string
	 */
	private function getTempRootDirectory(): string
	{
		$rootDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . FileField::FIELD_PREFIX . '__' . $_SERVER['SERVER_NAME'];
		if (!is_dir($rootDirectory)) {
			mkdir($rootDirectory);
		}

		return $rootDirectory;
	}

	/**
	 * Returns the path to the unique directory to store the temporary files based on a unique request key
	 * If directory does not exist, it will be created
	 *
	 * @return string
	 */
	private function getUniqueFilesDirectory(): string
	{
		$uniqueFilesDirectory = $this->getTempRootDirectory() . DIRECTORY_SEPARATOR . $this->getUniqueSessFileStorePointer();
		if (!is_dir($uniqueFilesDirectory)) {
			mkdir($uniqueFilesDirectory);
		}

		return $uniqueFilesDirectory;
	}

	private function saveNewFile(FileDataModel $fileDataModel): FileDataModel
	{
		// If tmp file already exists we just add a counter and increment it until we get a "free" file name
		$counter = 0;
		$dstFilePath = $baseFilePath = $this->getUniqueFilesDirectory() . DIRECTORY_SEPARATOR . basename($fileDataModel->getTmpName());
		while (file_exists($dstFilePath)) {
			$counter++;
			$dstFilePath = $baseFilePath . $counter;
		}

		// "move" (copy-del) it to fileStore (creating a new file pointer, therefore it does not get deleted from fileStore after script execution)
		move_uploaded_file($fileDataModel->getTmpName(), $dstFilePath);
		$fileDataModel->setTmpName($dstFilePath);

		return $fileDataModel;
	}

	public function getUniqueSessFileStorePointer(): string
	{
		return $this->uniqueSessFileStorePointer;
	}

	public function getMaxFileUploadCount(): int
	{
		return $this->maxFileUploadCount;
	}

	/**
	 * Returns a "clean" list about stored files, mainly for internal processing (because: hash)
	 *
	 * @return FileDataModel[] : Array with already uploaded files
	 */
	public function getFiles(): array
	{
		return $this->getRawValue();
	}

	/**
	 * Return an array with the removed file hash if we removed (or tried to) a file with the current request
	 * This information can be used by the form to prevent from further actions like the final processing
	 *
	 * @return array
	 */
	public function getRemovedValues(): array
	{
		return !is_null($this->deleteFileHash) ? [$this->deleteFileHash] : [];
	}

	/**
	 * Completely remove tmp directory with it's files
	 * To be used after successful form processing
	 */
	public function clearData(): void
	{
		$this->removeDirectory($this->getUniqueFilesDirectory());
	}

	/**
	 * Remove a directory and all files in it
	 *
	 * @param string $path
	 */
	private function removeDirectory(string $path): void
	{
		foreach (new DirectoryIterator($path) as $fileInfo) {
			if ($fileInfo->isFile()) {
				unlink($fileInfo->getPathname());
			}
		}
		rmdir($path);
	}

	/**
	 * Remove all temporary data older than 2 days
	 */
	public function removeOldFiles(): void
	{
		$rootDirectory = $this->getTempRootDirectory();
		foreach (new DirectoryIterator($rootDirectory) as $fileInfo) {
			if ($fileInfo->isDot()) {
				continue;
			}
			if ($fileInfo->isDir() && $fileInfo->getMTime() < time() - (60 * 60 * 24 * 2 /* 2 days */)) {
				$this->removeDirectory($fileInfo->getPathname());
			}
		}
	}
}