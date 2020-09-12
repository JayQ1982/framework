<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2020, Actra AG
 */

namespace framework\form\component\field;

use DirectoryIterator;
use framework\form\component\FormField;
use framework\form\extendedObjects\fileData;
use framework\form\FormRenderer;
use framework\form\listener\FileFieldListener;
use framework\form\renderer\FileFieldRenderer;
use framework\form\rule\RequiredRule;

class FileField extends FormField
{
	const PREFIX = 'fs';

	const VALUE_NAME = 'name';
	const VALUE_TMP_NAME = 'tmp_name';
	const VALUE_TYPE = 'type';
	const VALUE_ERROR = 'error';
	const VALUE_SIZE = 'size';

	// Hint: We need searchable Strings outside this class, therefore please do NOT insert dynamic Strings into them:
	const ERRMSG_FILE_EMPTY = 'Die Datei war leer: ';
	const ERRMSG_FILE_INCOMPLETE = 'Die Datei wurde unvollständig hochgeladen: ';
	const ERRMSG_FILE_TOO_BIG = 'Die Datei war zu gross: ';
	const ERRMSG_FILE_TECHERROR = 'Es ist ein technischer Fehler beim Hochladen der Datei aufgetreten: ';

	private int $maxFileUploadCount;
	private string $uniqueSessFileStorePointer;
	private string $tooManyFilesErrMsg;
	private ?string $deleteFileHash = null;

	/**
	 * @param string      $name
	 * @param string      $label
	 * @param string|null $requiredError      : NULL, if file upload is not required, otherwise the error message if no file was uploaded
	 * @param int         $maxFileUploadCount : Maximal amount of allowed files (1 by default) with that field
	 * @param string      $tooManyFilesErrMsg : Individual error message if more than allowed amount of files are uploaded. Placeholder [max] will be replaced
	 *                                        by the max amount.
	 */
	public function __construct(string $name, string $label, ?string $requiredError = null, int $maxFileUploadCount = 1, string $tooManyFilesErrMsg = 'Nur [max] Datei(en) möglich.')
	{
		if ($maxFileUploadCount < 1) {
			$maxFileUploadCount = 1; // silent correction
		}

		$this->maxFileUploadCount = $maxFileUploadCount;
		$this->uniqueSessFileStorePointer = $this->sanitizeUniqueID(uniqid(date('ymdHis') . '__', true));
		$this->tooManyFilesErrMsg = $tooManyFilesErrMsg;

		// To always handle value internally as array we force an empty array on initialization
		parent::__construct($name, $label, [], '(max. ' . $maxFileUploadCount . ')');

		if (!is_null($requiredError)) {
			$this->addRule(new RequiredRule($requiredError));
		}
	}

	private function sanitizeUniqueID(string $uid): string
	{
		// We do not allow dangerous characters in the pointer, as it will become part of
		//   an filesystem path; And we want to easily detect these later in the external input:
		return preg_replace('/[^a-z0-9_]/', '', $uid);
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
			if (isset($inputData[self::PREFIX]) && is_scalar($inputData[self::PREFIX])) {
				$receivedUid = trim($inputData[self::PREFIX]);
				// If that value is tampered by a "black hat hacker", he should just grab securely into an "empty bowl".
				//   Therefore we look for only allowed characters given in sanitizeUniqueID():
				$cleanedUid = $this->sanitizeUniqueID($receivedUid);
				if ($receivedUid === $cleanedUid) {
					// ONLY THEN take it:
					$this->uniqueSessFileStorePointer = $cleanedUid;
				}
			}

			if (isset($inputData[self::PREFIX . '_removeAttachment']) && is_scalar($inputData[self::PREFIX . '_removeAttachment'])) {
				// Referenced usage at FileFieldRenderer::prepare()
				$this->deleteFileHash = trim($inputData[self::PREFIX . '_removeAttachment']);
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
				if ($fileInfo[self::VALUE_ERROR] === UPLOAD_ERR_OK) {
					$formFieldListener->onUploadSuccess($this->topFormComponent, $this, $fileInfo);
				} else {
					$formFieldListener->onUploadFail($this->topFormComponent, $this, $fileInfo);
				}
			}
		}

		return !$this->hasErrors();
	}

	/**
	 * Restructures an input array of multiple files
	 *
	 * @param array $filesArr
	 *
	 * @return fileData[]
	 */
	protected function convertMultiFileArray(array $filesArr): array
	{
		$files = [];
		$filesCount = count($filesArr[self::VALUE_NAME]);

		for ($i = 0; $i < $filesCount; ++$i) {
			if ($filesArr[self::VALUE_ERROR][$i] === UPLOAD_ERR_NO_FILE) {
				// This represents "no files uploaded"
				continue;
			}

			$fileData = new fileData(
				trim($filesArr[self::VALUE_NAME][$i]),
				trim($filesArr[self::VALUE_TMP_NAME][$i]),
				trim($filesArr[self::VALUE_TYPE][$i]),
				(int)$filesArr[self::VALUE_ERROR][$i],
				(int)$filesArr[self::VALUE_SIZE][$i]
			);

			$files[] = $fileData;
		}

		return $files;
	}

	/**
	 * Get an array with all already uploaded files. Automatically removes files not existing (anymore) in file system.
	 *
	 * @return fileData[]
	 */
	private function getAlreadyUploadedFiles(): array
	{
		$usfsp = $this->getUniqueSessFileStorePointer();
		if (!isset($_SESSION[$usfsp])) {
			return $_SESSION[$usfsp] = [];
		}

		/** @var fileData $fileData */
		foreach ($_SESSION[$usfsp] as $hash => $fileData) {
			if (!file_exists($fileData->getTmpName())) {
				unset($_SESSION[$usfsp][$hash]);
			}
		}

		return $_SESSION[$usfsp];
	}

	/**
	 * @param null|array $dataArray : Array with additional (uploaded) files to be added
	 */
	public function setValue($dataArray = []): void
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
		if (is_array($dataArray)) {
			$fileArray = $this->addFilesFromDataArray($fileArray, $dataArray);
		}

		// Store new fileArray to session and current field value
		parent::setValue($_SESSION[$this->getUniqueSessFileStorePointer()] = $fileArray);
	}

	private function addFilesFromDataArray(array $originalFileArray, array $addFileArray): array
	{
		// Check if the data is available in the expected form
		if (
			!isset($addFileArray[self::VALUE_NAME])
			|| !isset($addFileArray[self::VALUE_TMP_NAME])
			|| !isset($addFileArray[self::VALUE_TYPE])
			|| !isset($addFileArray[self::VALUE_ERROR])
			|| !isset($addFileArray[self::VALUE_SIZE])
		) {
			return $originalFileArray;
		}

		// Convert input data into an array of fileData objects
		$convertedMultiFileArray = $this->convertMultiFileArray($addFileArray);

		// If new amount of files exceeds the limit, we add error and return the originalFileArray
		if ((count($originalFileArray) + count($convertedMultiFileArray)) > $this->maxFileUploadCount) {
			$this->addError(str_replace('[max]', $this->maxFileUploadCount, $this->tooManyFilesErrMsg));

			return $originalFileArray;
		}

		$newFileArray = $originalFileArray;

		foreach ($convertedMultiFileArray as $fileData) {
			// If upload was okay:
			if ($fileData->getError() === UPLOAD_ERR_OK) {
				// Special case from LIVE/PROD:
				if ($fileData->getSize() === 0) {
					$this->addError(self::ERRMSG_FILE_EMPTY . $fileData->getName());
					continue;
				}
				$fileData = $this->saveNewFile($fileData);
				// Usage of sha1 is save here
				$hash = sha1($fileData->getTmpName());
				$newFileArray[$hash] = $fileData;
				continue;
			}
			// Anything other are errors:
			switch ($fileData->getError()) {
				case UPLOAD_ERR_INI_SIZE : // fallthrough
				case UPLOAD_ERR_FORM_SIZE:
					$this->addError(self::ERRMSG_FILE_TOO_BIG . $fileData->getName());
					break;
				case UPLOAD_ERR_PARTIAL:
					$this->addError(self::ERRMSG_FILE_INCOMPLETE . $fileData->getName());
					break;
				case UPLOAD_ERR_NO_FILE:
					// will be silently ignored
					break;
				default:
					$this->addError(self::ERRMSG_FILE_TECHERROR . $fileData->getName());
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
		$rootDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::PREFIX . '__' . $_SERVER['SERVER_NAME'];
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

	/**
	 * Moves uploaded file to a save place
	 *
	 * @param fileData $fileData
	 *
	 * @return fileData $fileData
	 */
	private function saveNewFile(fileData $fileData): fileData
	{
		// If tmp file already exists we just add a counter and increment it until we get a "free" file name
		$counter = 0;
		$dstFilePath = $baseFilePath = $this->getUniqueFilesDirectory() . DIRECTORY_SEPARATOR . basename($fileData->getTmpName());
		while (file_exists($dstFilePath)) {
			$counter++;
			$dstFilePath = $baseFilePath . $counter;
		}

		// "move" (copy-del) it to fileStore (creating a new file pointer, therefore it does not get deleted from fileStore after script execution)
		move_uploaded_file($fileData->getTmpName(), $dstFilePath);
		$fileData->setTmpName($dstFilePath);

		return $fileData;
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
	 * @return fileData[] : Array with already uploaded files
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
/* EOF */