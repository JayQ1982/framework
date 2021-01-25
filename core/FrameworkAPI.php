<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use framework\api\curlHelper;
use framework\api\curlRequest;
use framework\api\response\curlResponse;
use RuntimeException;
use SimpleXMLElement;
use stdClass;

class FrameworkAPI extends curlHelper
{
	private Core $core;
	private ?string $lastErrMsg = null;
	private bool $returnFalseOnError = true;

	public function __construct(Core $core)
	{
		$this->core = $core;
	}

	public function returnFalseOnError(): void
	{
		$this->returnFalseOnError = true;
	}

	public function returnResultOnError(): void
	{
		$this->returnFalseOnError = false;
	}

	/**
	 * @param string            $apiTarget      : The target url which must be set in {/site/settings/envSettings.json}->apiTargets
	 * @param string            $path           : The path to be added to the target url
	 * @param array|string|null $postData       : The postData to be sent (see curlHelper for details)
	 * @param string            $responseFormat : Desired response format (raw|xml|json|array|object)
	 * @param int               $timeout        : Request timeout in seconds
	 * @param array             $curlOptions    : Array with additional curl options
	 *
	 * @return false|string|stdClass|curlRequest|SimpleXMLElement : The response from curl request
	 */
	public function curlRequest(string $apiTarget, string $path, $postData = null, string $responseFormat = curlResponse::RESPONSE_OBJECT, int $timeout = 10, $curlOptions = []): false|string|stdClass|curlRequest|SimpleXMLElement
	{
		$apiTargets = $this->core->getEnvironmentHandler()->getApiTargets();
		if (is_null($apiTargets)) {
			throw new RuntimeException('Missing apiTargets in site/settings/envSettings.json');
		}

		// Check, if we know the api target for this request.
		if (!isset($apiTargets->{$apiTarget})) {
			throw new RuntimeException(__CLASS__ . ': The target ' . $apiTarget . ' is unknown.');
		}

		$apiTargetSettings = $apiTargets->{$apiTarget};

		$url = $apiTargetSettings->url . $path;
		$curlRequest = $this->prepareRequest($url, $postData);
		$curlRequest->setRequestTimeoutInSeconds($timeout);
		foreach ($curlOptions as $key => $val) {
			$curlRequest->setCurlOption($key, $val);
		}

		if (isset($apiTargetSettings->ssl) && $apiTargetSettings->ssl === false) {
			$curlRequest->disableSSLcheck();
		}

		if (isset($apiTargetSettings->auth)) {
			$curlRequest->httpAuth((string)$apiTargetSettings->auth);
		}

		$executionResult = $curlRequest->execute();
		if ($executionResult === false) {
			$this->lastErrMsg = $curlRequest->getLastErrMsg();

			if ($this->returnFalseOnError) {
				return false;
			}
		}

		$responseFormat = mb_strtolower($responseFormat);
		switch ($responseFormat) {
			case curlResponse::RESPONSE_OBJECT:
				return $curlRequest;
			case curlResponse::RESPONSE_XML:
				$result = $curlRequest->getResponseBodyAsXml();
				break;
			case curlResponse::RESPONSE_JSON:
				$result = $curlRequest->getResponseBodyAsJson();
				break;
			default:
				$result = $curlRequest->getRawResponseBody();
				break;
		}
		if ($result === false) {
			$this->lastErrMsg = $curlRequest->getLastErrMsg();

			if ($this->returnFalseOnError) {
				return false;
			}
		}

		return $result;
	}

	public function getLastErrMsg(): ?string
	{
		return $this->lastErrMsg;
	}
}