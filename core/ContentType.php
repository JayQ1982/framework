<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\core;

class ContentType
{
	public const HTML = 'html';
	public const JSON = 'json';
	public const XML = 'xml';
	public const TXT = 'txt';
	public const CSV = 'csv';
	public const JS = 'js';
	public const CSS = 'css';
	public const JPG = 'jpg';
	public const GIF = 'gif';
	public const PNG = 'png';
	public const MOV = 'mov';

	public function __construct(
		public readonly string   $type,
		public readonly MimeType $mimeType,
		public readonly bool     $forceDownloadByDefault,
		public readonly ?string  $charset,
		public readonly ?string  $languageCode
	) {

	}

	public function isHtml(): bool
	{
		return ($this->type === ContentType::HTML);
	}

	public function isJson(): bool
	{
		return ($this->type === ContentType::JSON);
	}

	public function isTxt(): bool
	{
		return ($this->type === ContentType::TXT);
	}

	public function isCsv(): bool
	{
		return ($this->type === ContentType::CSV);
	}

	public function getHttpHeaderString(): string
	{
		$contentType = $this->mimeType->value;
		if (!is_null(value: $this->charset)) {
			$contentType .= '; charset=' . $this->charset;
		}

		return $contentType;
	}

	public static function createFromFileExtension(string $extension): ContentType
	{
		$extension = mb_strtolower(string: trim(string: $extension));

		return match ($extension) {
			ContentType::HTML => ContentType::createHtml(),
			ContentType::JSON => ContentType::createJson(),
			ContentType::XML => ContentType::createXml(),
			ContentType::TXT => ContentType::createTxt(),
			ContentType::CSV => ContentType::createCsv(),
			ContentType::JS => ContentType::createJs(),
			default => ContentType::createDefault(type: $extension)
		};
	}

	private static function createDefault(string $type): ContentType
	{
		return new ContentType(
			type: $type,
			mimeType: MimeType::createByFileExtension(extension: $type),
			forceDownloadByDefault: !in_array(needle: $type, haystack: [
				ContentType::CSS => false,
				ContentType::JPG => false,
				ContentType::GIF => false,
				ContentType::PNG => false,
				ContentType::MOV => false,
			]),
			charset: null,
			languageCode: null
		);
	}

	public static function createHtml(): ContentType
	{
		return new ContentType(
			type: ContentType::HTML,
			mimeType: MimeType::createHtml(),
			forceDownloadByDefault: false,
			charset: 'utf-8',
			languageCode: 'de'
		);
	}

	public static function createJson(): ContentType
	{
		return new ContentType(
			type: ContentType::JSON,
			mimeType: MimeType::createJson(),
			forceDownloadByDefault: false,
			charset: 'utf-8',
			languageCode: null
		);
	}

	public static function createXml(): ContentType
	{
		return new ContentType(
			type: ContentType::XML,
			mimeType: MimeType::createXml(),
			forceDownloadByDefault: false,
			charset: 'utf-8',
			languageCode: null
		);
	}

	public static function createTxt(): ContentType
	{
		return new ContentType(
			type: ContentType::TXT,
			mimeType: MimeType::createTxt(),
			forceDownloadByDefault: false,
			charset: 'utf-8',
			languageCode: null
		);
	}

	public static function createCsv(): ContentType
	{
		return new ContentType(
			type: ContentType::CSV,
			mimeType: MimeType::createCsv(),
			forceDownloadByDefault: true,
			charset: 'utf-8',
			languageCode: null
		);
	}

	public static function createJs(): ContentType
	{
		return new ContentType(
			type: ContentType::JS,
			mimeType: MimeType::createJs(),
			forceDownloadByDefault: false,
			charset: 'utf-8',
			languageCode: null
		);
	}
}