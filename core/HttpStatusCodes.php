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
	public const HTTP_CONTINUE = 100;
	public const HTTP_SWITCHING_PROTOCOLS = 101;
	public const HTTP_PROCESSING = 102;
	public const HTTP_CONNECTION_TIME_OUT = 118;

	// 2xx - Successful operation
	public const HTTP_OK = 200;
	public const HTTP_CREATED = 201;
	public const HTTP_ACCEPTED = 202;
	public const HTTP_NON_AUTHORATIVE_INFORMATION = 203;
	public const HTTP_NO_CONTENT = 204;
	public const HTTP_RESET_CONTENT = 205;
	public const HTTP_PARTIAL_CONTENT = 206;
	public const HTTP_MULTI_STATUS = 207;

	// 3xx - Redirection
	public const HTTP_MULTIPLE_CHOICES = 300;
	public const HTTP_MOVED_PERMANENTLY = 301;
	public const HTTP_FOUND = 302;
	public const HTTP_SEE_OTHER = 303;
	public const HTTP_NOT_MODIFIED = 304;
	public const HTTP_USE_PROXY = 305;
	public const HTTP_SWITCH_PROXY = 306;
	public const HTTP_TEMPORARY_REDIRECT = 307;

	// 4xx - Client errors
	public const HTTP_BAD_REQUEST = 400;
	public const HTTP_UNAUTHORIZED = 401;
	public const HTTP_PAYMENT_REQUIRED = 402;
	public const HTTP_FORBIDDEN = 403;
	public const HTTP_NOT_FOUND = 404;
	public const HTTP_METHOD_NOT_ALLOWED = 405;
	public const HTTP_NOT_ACCEPTABLE = 406;
	public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
	public const HTTP_REQUEST_TIME_OUT = 408;
	public const HTTP_CONFLICT = 409;
	public const HTTP_GONE = 410;
	public const HTTP_LENGTH_REQUIRED = 411;
	public const HTTP_PRECONDITION_FAILED = 412;
	public const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
	public const HTTP_REQUEST_URL_TOO_LONG = 414;
	public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
	public const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
	public const HTTP_EXPECTATION_FAILED = 417;
	public const HTTP_TOO_MANY_CONNECTIONS = 421;
	public const HTTP_UNPROCESSABLE_ENTITY = 422;
	public const HTTP_LOCKED = 423;
	public const HTTP_FAILE_DEPENDENCY = 424;
	public const HTTP_UNORDERED_COLLECTION = 425;
	public const HTTP_UPGRADE_REQUIRED = 426;
	public const HTTP_UNAVAILABLE_FOR_LEGAL_REASON = 451;

	// 5xx - server errors
	public const HTTP_INTERNAL_SERVER_ERROR = 500;
	public const HTTP_NOT_IMPLEMENTED = 501;
	public const HTTP_BAD_GATEWAY = 502;
	public const HTTP_SERVICE_UNAVAILABLE = 503;
	public const HTTP_GATEWAY_TIME_OUT = 504;
	public const HTTP_VERSION_NOT_SUPPORTED = 505;
	public const HTTP_VARIANT_ALSO_NEGOTIATES = 506;
	public const HTTP_INSUFFICIENT_STORAGE = 507;
	public const HTTP_BANDWITH_LIMIT_EXCEEDED = 509;
	public const HTTP_NOT_EXTENDED = 510;

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

		return $statusCodeDescriptions[$httpStatusCode] ?? null;
	}
}