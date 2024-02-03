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
use framework\form\model\FileDataModel;
use framework\form\renderer\FileFieldRenderer;
use framework\form\rule\RequiredRule;
use framework\html\HtmlEncoder;
use framework\html\HtmlText;

class FileField extends FormField
{
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

	private string $uniqueSessFileStorePointer;
	private HtmlText $tooManyFilesErrMsg;
	private HtmlText $alreadyExistsErrorMessage;
	private ?string $deleteFileHash = null;

	/**
	 * @param string        $name
	 * @param HtmlText      $label
	 * @param HtmlText|null $requiredError      NULL, if file upload is not required, otherwise the error message if no file was uploaded
	 * @param int           $maxFileUploadCount Maximal amount of allowed files (1 by default) with that field
	 * @param ?HtmlText     $tooManyFilesErrMsg Individual error message if more than allowed amount of files are uploaded. Placeholder [max] will be replaced
	 *                                          by the max amount.
	 * @param HtmlText|null $alreadyExistsErrorMessage
	 */
	public function __construct(
		string      $name,
		HtmlText    $label,
		?HtmlText   $requiredError = null,
		private int $maxFileUploadCount = 1,
		?HtmlText   $tooManyFilesErrMsg = null,
		?HtmlText   $alreadyExistsErrorMessage = null
	) {
		if ($this->maxFileUploadCount < 1) {
			$this->maxFileUploadCount = 1; // Silent correction
		}
		$this->uniqueSessFileStorePointer = $this->sanitizeUniqueID(uid: uniqid(prefix: $name . '__', more_entropy: true));
		$this->tooManyFilesErrMsg = is_null(value: $tooManyFilesErrMsg) ? HtmlText::encoded(textContent: 'Nur [max] Datei(en) möglich.') : $tooManyFilesErrMsg;
		$this->alreadyExistsErrorMessage = is_null(value: $alreadyExistsErrorMessage) ? HtmlText::encoded(textContent: 'Es wurde bereits eine Datei mit dem Dateinamen "[fileName]" hochgeladen.') : $alreadyExistsErrorMessage;
		// To always handle value internally as array we force an empty array on initialization
		parent::__construct(
			name: $name,
			label: $label,
			value: [],
			labelInfoText: HtmlText::encoded(textContent: '(max. ' . $this->maxFileUploadCount . ')')
		);
		if (!is_null(value: $requiredError)) {
			$this->addRule(formRule: new RequiredRule(defaultErrorMessage: $requiredError));
		}
	}

	private function sanitizeUniqueID(string $uid): string
	{
		// We do not allow dangerous characters in the pointer, as it will become part of a filesystem path;
		// And we want to easily detect these later in the external input:
		return preg_replace(
			pattern: '/[^a-zA-Z\d_]/',
			replacement: '',
			subject: $uid
		);
	}

	public function getDefaultRenderer(): FormRenderer
	{
		return new FileFieldRenderer(fileField: $this);
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
			if (array_key_exists(key: $this->getName() . '_UID', array: $inputData) && is_scalar(value: $inputData[$this->getName() . '_UID'])) {
				$receivedUid = Sanitizer::trimmedString(input: $inputData[$this->getName() . '_UID']);
				// If that value is tampered by a "black hat hacker", he should just grab securely into an "empty bowl".
				// Therefore, we look for only allowed characters given in sanitizeUniqueID():
				$cleanedUid = $this->sanitizeUniqueID(uid: $receivedUid);
				if ($receivedUid === $cleanedUid) {
					// ONLY THEN take it:
					$this->uniqueSessFileStorePointer = $cleanedUid;
				}
			}
			if (array_key_exists(key: $this->getName() . '_removeAttachment', array: $inputData) && is_scalar(value: $inputData[$this->getName() . '_removeAttachment'])) {
				// Referenced usage at FileFieldRenderer::prepare()
				$this->deleteFileHash = Sanitizer::trimmedString(input: $inputData[$this->getName() . '_removeAttachment']);
			}
		}

		return parent::validate(inputData: $inputData, overwriteValue: $overwriteValue);
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
		$filesCount = count(value: $filesArr[FileField::VALUE_NAME]);
		for ($i = 0; $i < $filesCount; ++$i) {
			if ($filesArr[FileField::VALUE_ERROR][$i] === UPLOAD_ERR_NO_FILE) {
				// This represents "no files uploaded"
				continue;
			}
			$fileDataModel = new FileDataModel(
				Sanitizer::trimmedString(input: $filesArr[FileField::VALUE_NAME][$i]),
				Sanitizer::trimmedString(input: $filesArr[FileField::VALUE_TMP_NAME][$i]),
				Sanitizer::trimmedString(input: $filesArr[FileField::VALUE_TYPE][$i]),
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
		if (!array_key_exists(key: $usfsp, array: $_SESSION)) {
			return $_SESSION[$usfsp] = [];
		}

		/** @var FileDataModel $fileDataModel */
		foreach ($_SESSION[$usfsp] as $hash => $fileDataModel) {
			if (!file_exists(filename: $fileDataModel->getTmpName())) {
				unset($_SESSION[$usfsp][$hash]);
			}
		}

		return $_SESSION[$usfsp];
	}

	/**
	 * @param null|array $value Array with additional (uploaded) files to be added
	 */
	public function setValue($value = []): void
	{
		// Always respect already uploaded files when (re)setting the value
		$fileArray = $this->getAlreadyUploadedFiles();
		// Remove an already uploaded file, if requested
		if (!is_null(value: $this->deleteFileHash) && array_key_exists(key: $this->deleteFileHash, array: $fileArray)) {
			if (file_exists(filename: $fileArray[$this->deleteFileHash]->getTmpName())) {
				unlink(filename: $fileArray[$this->deleteFileHash]->getTmpName());
			}
			unset($fileArray[$this->deleteFileHash]);
		}
		// Add new (uploaded) files to fileArray
		if (is_array(value: $value)) {
			$fileArray = $this->addFilesFromDataArray(originalFileArray: $fileArray, addFileArray: $value);
		}
		// Store new fileArray to session and current field value
		parent::setValue(value: $_SESSION[$this->getUniqueSessFileStorePointer()] = $fileArray);
	}

	/**
	 * @param FileDataModel[] $originalFileArray
	 * @param array           $addFileArray
	 *
	 * @return array
	 */
	private function addFilesFromDataArray(array $originalFileArray, array $addFileArray): array
	{
		// Check if the data is available in the expected form
		if (
			!array_key_exists(key: FileField::VALUE_NAME, array: $addFileArray)
			|| !array_key_exists(key: FileField::VALUE_TMP_NAME, array: $addFileArray)
			|| !array_key_exists(key: FileField::VALUE_TYPE, array: $addFileArray)
			|| !array_key_exists(key: FileField::VALUE_ERROR, array: $addFileArray)
			|| !array_key_exists(key: FileField::VALUE_SIZE, array: $addFileArray)
		) {
			return $originalFileArray;
		}
		// Convert input data into an array of fileData objects
		$convertedMultiFileArray = $this->convertMultiFileArray(filesArr: $addFileArray);
		// If new amount of files exceeds the limit, we add error and return the originalFileArray
		if ((count(value: $originalFileArray) + count(value: $convertedMultiFileArray)) > $this->maxFileUploadCount) {
			$this->addError(errorMessage: str_replace(search: '[max]', replace: $this->maxFileUploadCount, subject: $this->tooManyFilesErrMsg->render()), isEncodedForRendering: true);

			return $originalFileArray;
		}
		$existingFileNames = [];
		foreach ($originalFileArray as $fileDataModel) {
			$existingFileNames[] = $fileDataModel->getName();
		}
		$newFileArray = $originalFileArray;
		foreach ($convertedMultiFileArray as $fileDataModel) {
			$encodedFileName = HtmlEncoder::encode(value: $fileDataModel->getName());
			// If upload was okay:
			if ($fileDataModel->getError() === UPLOAD_ERR_OK) {
				if (in_array(needle: $fileDataModel->getName(), haystack: $existingFileNames)) {
					$this->addError(
						errorMessage: str_replace(
							search: '[fileName]',
							replace: $encodedFileName,
							subject: $this->alreadyExistsErrorMessage->render()
						),
						isEncodedForRendering: true
					);
					continue;
				}

				// Special case from LIVE/PROD:
				if ($fileDataModel->getSize() === 0) {
					$this->addError(errorMessage: FileField::ERRMSG_FILE_EMPTY . $encodedFileName, isEncodedForRendering: true);
					continue;
				}
				$fileDataModel = $this->saveNewFile(fileDataModel: $fileDataModel);
				// Usage of sha1 is safe here
				$hash = sha1(string: $fileDataModel->getTmpName());
				$newFileArray[$hash] = $fileDataModel;
				$existingFileNames[] = $fileDataModel->getName();
				continue;
			}
			// Anything other are errors
			switch ($fileDataModel->getError()) {
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$this->addError(errorMessage: FileField::ERRMSG_FILE_TOO_BIG . $encodedFileName, isEncodedForRendering: true);
					break;
				case UPLOAD_ERR_PARTIAL:
					$this->addError(errorMessage: FileField::ERRMSG_FILE_INCOMPLETE . $encodedFileName, isEncodedForRendering: true);
					break;
				case UPLOAD_ERR_NO_FILE:
					// Silently ignore
					break;
				default:
					$this->addError(errorMessage: FileField::ERRMSG_FILE_TECHERROR . $encodedFileName, isEncodedForRendering: true);
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
		$rootDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $_SERVER['SERVER_NAME'];
		if (!is_dir(filename: $rootDirectory)) {
			mkdir(directory: $rootDirectory);
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
		if (!is_dir(filename: $uniqueFilesDirectory)) {
			mkdir(directory: $uniqueFilesDirectory);
		}

		return $uniqueFilesDirectory;
	}

	private function saveNewFile(FileDataModel $fileDataModel): FileDataModel
	{
		// If tmp file already exists we just add a counter and increment it until we get a "free" file name
		$counter = 0;
		$dstFilePath = $baseFilePath = $this->getUniqueFilesDirectory() . DIRECTORY_SEPARATOR . basename(path: $fileDataModel->getTmpName());
		while (file_exists(filename: $dstFilePath)) {
			$counter++;
			$dstFilePath = $baseFilePath . $counter;
		}
		// "move" (copy-del) it to fileStore (creating a new file pointer, therefore it does not get deleted from fileStore after script execution)
		move_uploaded_file(from: $fileDataModel->getTmpName(), to: $dstFilePath);
		$fileDataModel->setTmpName(tmp_name: $dstFilePath);

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
	 * @return FileDataModel[] Array with already uploaded files
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
		return !is_null(value: $this->deleteFileHash) ? [$this->deleteFileHash] : [];
	}

	/**
	 * Completely remove tmp directory with its files
	 * To be used after successful form processing
	 */
	public function clearData(): void
	{
		$this->removeDirectory(path: $this->getUniqueFilesDirectory());
	}

	/**
	 * Remove a directory and all files in it
	 *
	 * @param string $path
	 */
	private function removeDirectory(string $path): void
	{
		/** @var DirectoryIterator $item */
		foreach (new DirectoryIterator(directory: $path) as $item) {
			if ($item->isFile()) {
				unlink(filename: $item->getPathname());
			}
		}
		rmdir(directory: $path);
	}

	/**
	 * Remove all temporary data older than 2 days
	 */
	public function removeOldFiles(): void
	{
		/** @var DirectoryIterator $item */
		foreach (new DirectoryIterator(directory: $this->getTempRootDirectory()) as $item) {
			if ($item->isDot()) {
				continue;
			}
			if ($item->isDir() && $item->getMTime() < time() - (60 * 60 * 24 * 2 /* 2 days */)) {
				$this->removeDirectory(path: $item->getPathname());
			}
		}
	}
}