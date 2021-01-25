<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Copyright (c) 2021, Actra AG
 */

namespace framework\core;

use ReflectionClass;

class HttpStatusCodes
{
	// 1xx - Informations
	const HTTP_CONTINUE = 100;
	const HTTP_SWITCHING_PROTOCOLS = 101;
	const HTTP_PROCESSING = 102;
	const HTTP_CONNECTION_TIME_OUT = 118;

	// 2xx - Successful operation
	const HTTP_OK = 200;
	const HTTP_CREATED = 201;
	const HTTP_ACCEPTED = 202;
	const HTTP_NON_AUTHORATIVE_INFORMATION = 203;
	const HTTP_NO_CONTENT = 204;
	const HTTP_RESET_CONTENT = 205;
	const HTTP_PARTIAL_CONTENT = 206;
	const HTTP_MULTI_STATUS = 207;

	// 3xx - Redirection
	const HTTP_MULTIPLE_CHOICES = 300;
	const HTTP_MOVED_PERMANENTLY = 301;
	const HTTP_FOUND = 302;
	const HTTP_SEE_OTHER = 303;
	const HTTP_NOT_MODIFIED = 304;
	const HTTP_USE_PROXY = 305;
	const HTTP_SWITCH_PROXY = 306;
	const HTTP_TEMPORARY_REDIRECT = 307;

	// 4xx - Client errors
	const HTTP_BAD_REQUEST = 400;
	const HTTP_UNAUTHORIZED = 401;
	const HTTP_PAYMENT_REQUIRED = 402;
	const HTTP_FORBIDDEN = 403;
	const HTTP_NOT_FOUND = 404;
	const HTTP_METHOD_NOT_ALLOWED = 405;
	const HTTP_NOT_ACCEPTABLE = 406;
	const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
	const HTTP_REQUEST_TIME_OUT = 408;
	const HTTP_CONFLICT = 409;
	const HTTP_GONE = 410;
	const HTTP_LENGTH_REQUIRED = 411;
	const HTTP_PRECONDITION_FAILED = 412;
	const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
	const HTTP_REQUEST_URL_TOO_LONG = 414;
	const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
	const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	const HTTP_EXPECTATION_FAILED = 417;
	const HTTP_TOO_MANY_CONNECTIONS = 421;
	const HTTP_UNPROCESSABLE_ENTITY = 422;
	const HTTP_LOCKED = 423;
	const HTTP_FAILE_DEPENDENCY = 424;
	const HTTP_UNORDERED_COLLECTION = 425;
	const HTTP_UPGRADE_REQUIRED = 426;
	const HTTP_UNAVAILABLE_FOR_LEGAL_REASON = 451;

	// 5xx - server errors
	const HTTP_INTERNAL_SERVER_ERROR = 500;
	const HTTP_NOT_IMPLEMENTED = 501;
	const HTTP_BAD_GATEWAY = 502;
	const HTTP_SERVICE_UNAVAILABLE = 503;
	const HTTP_GATEWAY_TIME_OUT = 504;
	const HTTP_VERSION_NOT_SUPPORTED = 505;
	const HTTP_VARIANT_ALSO_NEGOTIATES = 506;
	const HTTP_INSUFFICIENT_STORAGE = 507;
	const HTTP_BANDWITH_LIMIT_EXCEEDED = 509;
	const HTTP_NOT_EXTENDED = 510;

	public static function getAllStatusCodes(): array
	{
		$reflectionClass = new ReflectionClass(__CLASS__);

		return array_flip($reflectionClass->getConstants());
	}

	public static function getStatusHeader(int $httpStatusCode): string
	{
		$statusHeader = 'HTTP/1.1 ' . $httpStatusCode;
		$statusCodeDescription = HttpStatusCodes::getStatusCodeDescription($httpStatusCode);
		if (!is_null($statusCodeDescription)) {
			$statusHeader .= ' ' . $statusCodeDescription;
		}

		return $statusHeader;
	}

	public static function getStatusCodeDescription(int $httpStatusCode): ?string
	{
		$statusCodeDescriptions = [
			HttpStatusCodes::HTTP_OK                            => 'OK',
			HttpStatusCodes::HTTP_NOT_MODIFIED                  => 'Not Modified',
			HttpStatusCodes::HTTP_BAD_REQUEST                   => 'Bad Request',
			HttpStatusCodes::HTTP_UNAUTHORIZED                  => 'Unauthorized',
			HttpStatusCodes::HTTP_PAYMENT_REQUIRED              => 'Payment Required',
			HttpStatusCodes::HTTP_FORBIDDEN                     => 'Forbidden',
			HttpStatusCodes::HTTP_NOT_FOUND                     => 'Not found',
			HttpStatusCodes::HTTP_METHOD_NOT_ALLOWED            => 'Method Not Allowed',
			HttpStatusCodes::HTTP_NOT_ACCEPTABLE                => 'Not Acceptable',
			HttpStatusCodes::HTTP_PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
			HttpStatusCodes::HTTP_REQUEST_TIME_OUT              => 'Request Time-out',
			HttpStatusCodes::HTTP_CONFLICT                      => 'Conflict',
			HttpStatusCodes::HTTP_GONE                          => 'Gone',
			HttpStatusCodes::HTTP_LENGTH_REQUIRED               => 'Length Required',
			HttpStatusCodes::HTTP_PRECONDITION_FAILED           => 'Precondition Failed',
			HttpStatusCodes::HTTP_REQUEST_ENTITY_TOO_LARGE      => 'Request Entity Too Large',
			HttpStatusCodes::HTTP_REQUEST_URL_TOO_LONG          => 'Request-URI Too Long',
			HttpStatusCodes::HTTP_UNSUPPORTED_MEDIA_TYPE        => 'Unsupported Media Type',
			HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR         => 'Internal Server Error',
			HttpStatusCodes::HTTP_NOT_IMPLEMENTED               => 'Not Implemented',
			HttpStatusCodes::HTTP_BAD_GATEWAY                   => 'Bad Gateway',
			HttpStatusCodes::HTTP_SERVICE_UNAVAILABLE           => 'Service Unavailable',
			HttpStatusCodes::HTTP_GATEWAY_TIME_OUT              => 'Gateway Time-out',
			HttpStatusCodes::HTTP_VERSION_NOT_SUPPORTED         => 'HTTP Version not supported',
		];

		return isset($statusCodeDescriptions[$httpStatusCode]) ? $statusCodeDescriptions[$httpStatusCode] : null;
	}
}