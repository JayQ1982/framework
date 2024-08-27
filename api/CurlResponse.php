<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\api;

use CurlHandle;
use framework\common\JsonUtils;
use framework\common\SimpleXMLExtended;
use framework\core\HttpStatusCode;
use stdClass;

class CurlResponse
{
	public const ERROR_BAD_HTTP_RESPONSE_CODE = 900;

	private function __construct(
		public readonly false|string   $rawResponseBody,
		public readonly array          $curlInfo,
		public readonly HttpStatusCode $responseHttpCode,
		public readonly float          $totalRequestTime,
		public readonly int            $errorCode,
		public readonly string         $errorMessage
	) {
	}

	public static function createFromPreparedCurlHandle(CurlHandle $preparedCurlHandle, bool $acceptRedirectionResponseCode): CurlResponse
	{
		$rawResponseBody = curl_exec(handle: $preparedCurlHandle);
		$curlInfo = curl_getinfo(handle: $preparedCurlHandle);
		$responseHttpCode = HttpStatusCode::tryFrom(value: (int)$curlInfo['http_code']);
		if (is_null(value: $responseHttpCode)) {
			$responseHttpCode = HttpStatusCode::HTTP_UNKNOWN;
		}
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
			$responseHttpCode >= HttpStatusCode::HTTP_MULTIPLE_CHOICES
			&& $responseHttpCode->value < 600
			&& (
				!$acceptRedirectionResponseCode
				|| !in_array(needle: $responseHttpCode, haystack: [
					HttpStatusCode::HTTP_MOVED_PERMANENTLY,
					HttpStatusCode::HTTP_SEE_OTHER,
				])
			)
		) {
			$errorCode = CurlResponse::ERROR_BAD_HTTP_RESPONSE_CODE;
			$errorMessage = __CLASS__ . ': Bad HTTP response code received: ' . $responseHttpCode->value;
			$errorMessage .= match ($responseHttpCode) {
				HttpStatusCode::HTTP_MOVED_PERMANENTLY => ' ("moved permanently". Check URL/settings.)',
				HttpStatusCode::HTTP_SEE_OTHER => ' ("Redirect". Maybe HTTP-to-HTTPS? Check URL/settings.)',
				HttpStatusCode::HTTP_UNAUTHORIZED => ' ("unauthorized". Check credentials or request format.)',
				HttpStatusCode::HTTP_NOT_FOUND => ' ("not found" on server)',
				HttpStatusCode::HTTP_METHOD_NOT_ALLOWED => ' ("method not allowed". Check URL or request format/data.)',
				HttpStatusCode::HTTP_NOT_ACCEPTABLE => ' ("not acceptable" on server. Check request format/data.)',
				HttpStatusCode::HTTP_INTERNAL_SERVER_ERROR => ' (remote "Server error")',
				default => ''
			};
		}

		return new CurlResponse(
			rawResponseBody: $rawResponseBody,
			curlInfo: $curlInfo,
			responseHttpCode: $responseHttpCode,
			totalRequestTime: (float)$curlInfo['total_time'],
			errorCode: $errorCode,
			errorMessage: $errorMessage
		);
	}

	public function hasErrors(): bool
	{
		return ($this->errorCode !== CURLE_OK);
	}

	public function getJsonResponse(): stdClass|array
	{
		return JsonUtils::decodeJsonString(jsonString: $this->rawResponseBody, returnAssociativeArray: false);
	}

	public function getXmlResponse(): SimpleXMLExtended
	{
		return new SimpleXMLExtended(data: $this->rawResponseBody, options: LIBXML_NOCDATA);
	}
}