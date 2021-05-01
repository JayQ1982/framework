<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\api;

use CurlHandle;
use Exception;
use framework\api\response\curlResponse;
use framework\api\response\jsonCurlResponse;
use framework\api\response\xmlCurlResponse;
use framework\common\SimpleXMLExtended;
use framework\core\HttpStatusCodes;
use RuntimeException;
use stdClass;

/**
 * This class represents one curl request to the given url. Although it's possible,
 * you SHOULD NOT INSTANTIATE IT DIRECTLY but use a helper class like FrameworkAPI (which extends curlHelper).
 * Unfortunately in php there is no function to directly determine the callee (like in js). Using backtrace,
 * that's helpful for debugging, is too cost intensive.
 */
class curlRequest
{
	const TYPE_DELETE = 'DELETE';
	const TYPE_PUT = 'PUT';
	const TYPE_GET = 'GET';
	const TYPE_POST = 'POST';
	const TYPE_PATCH = 'PATCH';
	const ALLOWED_REQUEST_TYPES = [
		curlRequest::TYPE_DELETE,
		curlRequest::TYPE_PUT,
		curlRequest::TYPE_GET,
		curlRequest::TYPE_POST,
		curlRequest::TYPE_PATCH,
	];

	private string $lastErrMsg = ''; // Gather the very last error message so we can display it.
	private string $lastErrCode = '0'; // Gather the very last error code so we can display it. Might not be an INT in every case ...
	private static ?CurlHandle $curlHandle = null; // Static to use connection persistence for multiple requests to the same url
	private static int $instanceCounter = 0; // Counter for instances of this class so we can properly close curl in destructor for the last instance
	private string $url; // Target url for this request
	private ?string $postData = null; // Post data to be used in request
	private int $requestTimeoutInSeconds = 10;
	private int $connectTimeoutInSeconds = 10;
	private array $httpHeaders = []; // Array with http headers (CURLOPT_HTTPHEADER)
	private array $curlOptions = []; // Array with curl options
	private ?string $responseBody = null; // Response body after execution
	private ?array $responseHeader = null; // Response header after execution
	private ?int $responseHttpCode = null; // http Code after execution
	private bool $allowRedirect = false; // Allow 301 redirects (set to false by default)
	private ?bool $success = null; // null (default) before execution, true/false after execution
	private ?string $requestType = null; // The request type which can be DELETE, PUT, GET or POST - automatically determined if not set

	/**
	 * curlRequest constructor.
	 *
	 * @param string $url : Complete service url for request
	 */
	public function __construct(string $url)
	{
		curlRequest::$instanceCounter++;
		$this->url = $url;
		$this->setLastErrMsg('execute() was not called.');
	}

	public function __destruct()
	{
		curlRequest::$instanceCounter--;
		// If "curl" was used, close it properly with last instance of this class
		if (curlRequest::$instanceCounter == 0 && !is_null(curlRequest::$curlHandle)) {
			curl_close(curlRequest::$curlHandle);
		}
	}

	/**
	 * Manually set the desired request type if we don't want to detect it automatically
	 *
	 * @param string $requestType
	 *
	 * @throws Exception
	 */
	public function setRequestType(string $requestType): void
	{
		if (!in_array($requestType, curlRequest::ALLOWED_REQUEST_TYPES)) {
			throw new Exception('Invalid request type ' . $requestType . '. Allowed values are ' . implode(', ', curlRequest::ALLOWED_REQUEST_TYPES));
		}
		$this->requestType = $requestType;
	}

	public function setPostDataFromArray(array $array): void
	{
		$this->postData = http_build_query($this->all_to_string($array), '', '&', PHP_QUERY_RFC3986);
		$this->addHttpHeader('Content-Type: application/x-www-form-urlencoded; charset=utf-8'); // we keep that, although "PHP_QUERY_RFC3986" is used. Because "Cerberus".
	}

	public function setPostDataFromObject(stdClass $object): void
	{
		$this->setPostDataFromArray((array)$object);
	}

	public function setPostDataFromXML(string $xmlString): void
	{
		$this->postData = $xmlString;
		$this->addHttpHeader('Content-Type: text/xml; charset=utf-8');
		$this->addHttpHeader('HTTP_PRETTY_PRINT: TRUE');
	}

	public function setPostDataFromJson(string $jsonString): void
	{
		$this->postData = $jsonString;
		$this->addHttpHeader('Content-Type: application/json; charset=utf-8');
	}

	public function setPostDataFromPlainText(string $plainText): void
	{
		$this->postData = $plainText;
		$this->addHttpHeader('Content-Type: text/plain; charset=utf-8');
	}

	/**
	 * @param string $dataString
	 * @param bool   $detectContentType : Set to true to automatically detect the content type
	 */
	public function setPostDataFromString(string $dataString, bool $detectContentType = false): void
	{
		if (!$detectContentType) {
			$this->setPostDataFromPlainText($dataString);
		} else {

			switch (mb_substr($dataString, 0, 1)) {
				case '<':
					$this->setPostDataFromXML($dataString);
					break;
				case '{':
					$this->setPostDataFromJson($dataString);
					break;
				default:
					$this->setPostDataFromPlainText($dataString);
					break;
			}
		}
	}

	public function getPostData(): ?string
	{
		return $this->postData;
	}

	public function setConnectTimeoutInSeconds(int $connectTimeoutInSeconds): void
	{
		$this->connectTimeoutInSeconds = $connectTimeoutInSeconds;
	}

	public function setRequestTimeoutInSeconds(int $requestTimeoutInSeconds): void
	{
		$this->requestTimeoutInSeconds = $requestTimeoutInSeconds;
	}

	/**
	 * Add an additional CURLOPT_HTTPHEADER element
	 *
	 * @param string $value : Additional value to be added for CURLOPT_HTTPHEADER
	 */
	public function addHttpHeader(string $value): void
	{
		$this->httpHeaders[] = $value;
	}

	/**
	 * Allows to set some CURL options. If an option is set to null, it will be unset.
	 *
	 * @param int $name  : constant identifying the type of CURL option (except CURLOPT_HTTPHEADER)
	 * @param     $value : value for this option name
	 *
	 * @throws RuntimeException
	 */
	public function setCurlOption(int $name, $value): void
	{
		if ($name === CURLOPT_HTTPHEADER) {
			throw new RuntimeException('Please use addHttpHeader() to expand CURLOPT_HTTPHEADER.');
		}
		$this->curlOptions[$name] = $value;
	}

	/**
	 * Disables the SSL check for this curl request
	 * Hint:
	 * "SSL certificate problem: unable to get local issuer certificate" - this can
	 * be solved by saving a local cacert.pem (containing the certificates of Root CAs)
	 * and configure PHP to use it:  "curl.cainfo=/path/to/cacert.pem".
	 *
	 * @throws RuntimeException
	 */
	public function disableSSLcheck(): void
	{
		$this->setCurlOption(CURLOPT_SSL_VERIFYHOST, 0);
		$this->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
	}

	public function allowRedirect(bool $newValue): void
	{
		$this->allowRedirect = $newValue;
	}

	/**
	 * Use http authentication
	 *
	 * @param string $userPwd : username:password to be used for http authentication
	 *
	 * @throws RuntimeException
	 */
	public function httpAuth(string $userPwd): void
	{
		$this->setCurlOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		$this->setCurlOption(CURLOPT_USERPWD, $userPwd);
	}

	/**
	 * Execute the curl request
	 *
	 * @return bool: true on success, false if any error occurred
	 */
	public function execute(): bool
	{
		// Set curl resource
		if (!is_null(curlRequest::$curlHandle)) {
			// Reuse curl resource, if already initialized before
			curl_reset(curlRequest::$curlHandle);
		} else {
			curlRequest::$curlHandle = curl_init();

			if (curlRequest::$curlHandle === false) {
				throw new RuntimeException(__CLASS__ . ': Could not initialize a CURL handle.');
			}
		}

		$this->setLastErrMsg('');

		// If request type was not set manually before, we determine it (GET or POST) based on postData
		if (is_null($this->requestType)) {
			if (is_null($this->postData)) {
				$this->requestType = curlRequest::TYPE_GET;
			} else {
				$this->requestType = curlRequest::TYPE_POST;
			}
		}

		// Set content length, if postData is not empty
		if (trim($this->postData) != '') {
			$this->addHttpHeader('Content-Length: ' . strlen($this->postData));
		}

		// Set default curl options
		$curlOpts = [
			CURLOPT_URL            => $this->url,
			CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutInSeconds,
			CURLOPT_TIMEOUT        => $this->requestTimeoutInSeconds,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => $this->httpHeaders,
		];

		// Set request type specific options
		switch ($this->requestType) {
			case curlRequest::TYPE_GET:
				$curlOpts[CURLOPT_HTTPGET] = true;
				break;

			case curlRequest::TYPE_POST:
				$curlOpts[CURLOPT_POST] = true;
				$curlOpts[CURLOPT_POSTFIELDS] = $this->postData;
				break;

			case curlRequest::TYPE_PUT:
			case curlRequest::TYPE_PATCH:
				$curlOpts[CURLOPT_CUSTOMREQUEST] = $this->requestType;
				$curlOpts[CURLOPT_POSTFIELDS] = $this->postData;
				break;

			case curlRequest::TYPE_DELETE:
				$curlOpts[CURLOPT_CUSTOMREQUEST] = $this->requestType;
				break;
		}

		// setup curl additional options
		foreach ($this->curlOptions as $name => $value) {
			if (is_null($value) && isset($curlOpts[$name])) {
				unset($curlOpts[$name]);
			} else {
				$curlOpts[$name] = $value;
			}
		}
		curl_setopt_array(curlRequest::$curlHandle, $curlOpts);

		$this->responseBody = curl_exec(curlRequest::$curlHandle);
		$this->responseHeader = curl_getinfo(curlRequest::$curlHandle);
		$this->responseHttpCode = intval($this->responseHeader['http_code'] ?? 0);

		if (
			$this->responseHttpCode >= HttpStatusCodes::HTTP_MULTIPLE_CHOICES
			&& $this->responseHttpCode < 600
			&& (
				$this->allowRedirect === false
				|| !in_array($this->responseHttpCode, [HttpStatusCodes::HTTP_MOVED_PERMANENTLY, HttpStatusCodes::HTTP_SEE_OTHER]
				)
			)
		) {
			// That was definitely a bad response
			$text = __CLASS__ . ': Bad HTTP response code received: ' . $this->responseHttpCode;
			$text .= match ($this->responseHttpCode) {
				HttpStatusCodes::HTTP_MOVED_PERMANENTLY => ' ("moved permanently". Check URL/settings.)',
				HttpStatusCodes::HTTP_SEE_OTHER => ' ("Redirect". Maybe HTTP-to-HTTPS? Check URL/settings.)',
				HttpStatusCodes::HTTP_UNAUTHORIZED => ' ("unauthorized". Check credentials or request format.)',
				HttpStatusCodes::HTTP_NOT_FOUND => ' ("not found" on server)',
				HttpStatusCodes::HTTP_METHOD_NOT_ALLOWED => ' ("method not allowed". Check URL or request format/data.)',
				HttpStatusCodes::HTTP_NOT_ACCEPTABLE => ' ("not acceptable" on server. Check request format/data.)',
				HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR => ' (remote "Server error")',
				default => ''
			};
			$text .= '. URL was: ' . parse_url($this->url, PHP_URL_PATH);
			$this->setLastErrMsg($text);

			return $this->success = false;
		}

		if ($this->responseBody === false) {
			// Try to detect problem for faster understanding in logs/protocols
			$errNo = curl_errno(curlRequest::$curlHandle);
			$errMsg = __CLASS__ . ': (' . $errNo . ') ' . curl_error(curlRequest::$curlHandle);
			// See http://www.php.net/manual/en/function.curl-errno.php for further values of interest.
			$errMsg .= match ($errNo) {
				9 => '; Hint: (Remote) Access denied.',
				35 => '; Hint: Problem with ssl connection.',
				45 => '; Hint: Interface failed. Maybe problem with networking on server?',
				52 => '; Hint: Got no data.',
				58 => '; Hint: Problem with certificate on ssl connection.',
				60 => '; Hint: Problem with CA certificate on ssl connection. Maybe OS update missing on server?',
				67 => '; Hint: Login denied.',
				default => ''
			};
			$this->setLastErrMsg($errMsg);

			return $this->success = false;
		}

		// Return true on success
		return $this->success = true;
	}

	public function getRawResponseBody(): bool|string|null
	{
		return $this->responseBody;
	}

	public function getResponseBodyAsXml(): SimpleXMLExtended|bool
	{
		return $this->formatFetcher(new xmlCurlResponse($this));
	}

	public function getResponseBodyAsJson(): bool|stdClass
	{
		return $this->formatFetcher(new jsonCurlResponse($this));
	}

	/**
	 * Fetches the desired format defined by given class, and on error sets the correct internal error message
	 *
	 * @param curlResponse $curlResponseObject : A (derived) instantiation of abstract class "curlResponse"
	 *
	 * @return false|array|stdClass|SimpleXMLExtended
	 */
	private function formatFetcher(curlResponse $curlResponseObject): SimpleXMLExtended|false|array|stdClass
	{
		$value = $curlResponseObject->get();
		if ($value === false) {
			$this->setLastErrMsg($curlResponseObject->getConvertErrorMessage());
		}

		return $value;
	}

	public function getResponseHeader(): ?array
	{
		return $this->responseHeader;
	}

	public function getResponseHttpCode(): ?int
	{
		return $this->responseHttpCode;
	}

	/**
	 * Indicates precisely, if executed curl operation was technically a success
	 * In case of an error, more information about it _might_ be gained by getLastErrMsg()
	 *
	 * @return bool : Boolean indicator of failure/success
	 */
	public function wasSuccessful(): bool
	{
		if (is_null($this->success)) {
			// a non-executed curl is NOT a success
			return false;
		}

		return $this->success;
	}

	/**
	 * Convert each element of (multi-dimensional) arrays and objects into strings which is needed
	 * to be used as post data in http requests (curl).
	 *
	 * @param $data
	 *
	 * @return int|array|string
	 */
	protected function all_to_string($data): int|array|string
	{
		if (is_bool($data)) {
			$data = ($data) ? 1 : 0;
			settype($data, 'string');

			return $data;
		}

		if (is_object($data)) {
			$arrPrepared = [];
			foreach (get_object_vars($data) as $strKey => $val) {
				$strKey = $this->all_to_string($strKey);
				$val = $this->all_to_string($val);
				$arrPrepared[$strKey] = $val;
			}

			return $arrPrepared;
		}

		if (is_array($data)) {
			$arrPrepared = [];
			foreach ($data as $strKey => $val) {
				$strKey = $this->all_to_string($strKey);
				$val = $this->all_to_string($val);
				$arrPrepared[$strKey] = $val;
			}

			return $arrPrepared;
		}

		settype($data, 'string');

		return $data;
	}

	/**
	 * Sets an error for later processing by calling processes (for example "output in XML").
	 *
	 * @param string $errMsg : The error message
	 */
	protected function setLastErrMsg(string $errMsg): void
	{
		$this->lastErrMsg = $errMsg;
		$this->lastErrCode = '0';
	}

	/**
	 * Sets an error for later processing by calling processes (for example "output in XML").
	 *
	 * @param string $errMsg  : The error message
	 * @param        $errCode : The optional error code
	 */
	protected function setLastErr(string $errMsg, $errCode = ''): void
	{
		$this->lastErrMsg = $errMsg;
		$this->lastErrCode = (string)$errCode;
		// not every return code is an integer (see for example PDO),
		// but this class guarantees to return only ONE type: string.
	}

	public function getLastErrMsg(): string
	{
		return $this->lastErrMsg;
	}

	protected function getLastErrCode(): string
	{
		return $this->lastErrCode;
	}
}