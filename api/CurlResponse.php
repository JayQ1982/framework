<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) Actra AG, Rümlang, Switzerland
 */

namespace framework\api;

use CurlHandle;
use framework\common\JsonUtils;
use framework\common\SimpleXMLExtended;
use framework\core\HttpStatusCodes;
use stdClass;

class CurlResponse
{
	public const ERROR_BAD_HTTP_RESPONSE_CODE = 900;
	public const ERROR_BAD_RESPONSE_DATA = 903;

	private false|string $rawResponseBody;
	private array $curlInfo;
	private int $responseHttpCode;
	private float $totalRequestTime;
	private int $errorCode;
	private string $errorMessage;

	private function __construct(
		false|string $rawResponseBody,
		array        $curlInfo,
		int          $responseHttpCode,
		float          $totalRequestTime,
		int          $errorCode,
		string       $errorMessage
	) {
		$this->rawResponseBody = $rawResponseBody;
		$this->curlInfo = $curlInfo;
		$this->responseHttpCode = $responseHttpCode;
		$this->totalRequestTime = $totalRequestTime;
		$this->errorCode = $errorCode;
		$this->errorMessage = $errorMessage;
	}

	public static function createFromPreparedCurlHandle(CurlHandle $preparedCurlHandle, bool $acceptRedirectionResponseCode): CurlResponse
	{
		$rawResponseBody = curl_exec(handle: $preparedCurlHandle);
		$curlInfo = curl_getinfo(handle: $preparedCurlHandle);
		$responseHttpCode = $curlInfo['http_code'];
		$errorCode = curl_errno(handle: $preparedCurlHandle);
		$errorMessage = curl_error(handle: $preparedCurlHandle);

		if ($errorCode !== CURLE_OK) {
			$errorMessage = __CLASS__ . ': (' . $errorCode . ') ' . $errorMessage;
			// See http://www.php.net/manual/en/function.curl-errno.php for further values of interest.
			$errorMessage .= match ($errorCode) {
				CURLE_FTP_ACCESS_DENIED => '; Hint: (Remote) Access denied.',
				CURLE_SSL_CONNECT_ERROR => '; Hint: Problem with ssl connection.',
				CURLE_HTTP_PORT_FAILED => '; Hint: Interface failed. Maybe problem with networking on server?',
				CURLE_GOT_NOTHING => '; Hint: Got no data.',
				CURLE_SSL_CERTPROBLEM => '; Hint: Problem with certificate on ssl connection.',
				CURLE_SSL_PEER_CERTIFICATE => '; Hint: Problem with CA certificate on ssl connection. Maybe OS update missing on server?',
				67 => '; Hint: Login denied.',
				default => ''
			};
		} else if (
			$responseHttpCode >= HttpStatusCodes::HTTP_MULTIPLE_CHOICES
			&& $responseHttpCode < 600
			&& (
				!$acceptRedirectionResponseCode
				|| !in_array($responseHttpCode, [HttpStatusCodes::HTTP_MOVED_PERMANENTLY, HttpStatusCodes::HTTP_SEE_OTHER])
			)
		) {
			$errorCode = CurlResponse::ERROR_BAD_HTTP_RESPONSE_CODE;
			$errorMessage = __CLASS__ . ': Bad HTTP response code received: ' . $responseHttpCode;
			$errorMessage .= match ($responseHttpCode) {
				HttpStatusCodes::HTTP_MOVED_PERMANENTLY => ' ("moved permanently". Check URL/settings.)',
				HttpStatusCodes::HTTP_SEE_OTHER => ' ("Redirect". Maybe HTTP-to-HTTPS? Check URL/settings.)',
				HttpStatusCodes::HTTP_UNAUTHORIZED => ' ("unauthorized". Check credentials or request format.)',
				HttpStatusCodes::HTTP_NOT_FOUND => ' ("not found" on server)',
				HttpStatusCodes::HTTP_METHOD_NOT_ALLOWED => ' ("method not allowed". Check URL or request format/data.)',
				HttpStatusCodes::HTTP_NOT_ACCEPTABLE => ' ("not acceptable" on server. Check request format/data.)',
				HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR => ' (remote "Server error")',
				default => ''
			};
		}

		return new CurlResponse(
			rawResponseBody: $rawResponseBody,
			curlInfo: $curlInfo,
			responseHttpCode: $responseHttpCode,
			totalRequestTime: $curlInfo['total_time'],
			errorCode: $errorCode,
			errorMessage: $errorMessage
		);
	}

	public function hasErrors(): bool
	{
		return ($this->errorCode !== CURLE_OK);
	}

	/**
	 * @return array
	 */
	public function getCurlInfo(): array
	{
		return $this->curlInfo;
	}

	public function getResponseHttpCode(): int
	{
		return $this->responseHttpCode;
	}

	public function getTotalRequestTime(): float
	{
		return $this->totalRequestTime;
	}

	public function getErrorCode(): int
	{
		return $this->errorCode;
	}

	public function getErrorMessage(): string
	{
		return $this->errorMessage;
	}

	public function getRawResponseBody(): false|string
	{
		return $this->rawResponseBody;
	}

	public function getJsonResponse(): stdClass
	{
		return JsonUtils::decodeJsonString(jsonString: $this->rawResponseBody, returnAssociativeArray: false);
	}

	public function getXmlResponse(): SimpleXMLExtended
	{
		return new SimpleXMLExtended(data: $this->rawResponseBody, options: LIBXML_NOCDATA);
	}
}