<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

use Throwable;

class ImageResizer
{
	public static function resize(
		string $temporaryPath,
		string $fileExtension,
		string $destinationDirectory = '',
		string $newImageName = '',
		int    $thumbnailWidth = 0,
		int    $thumbnailHeight = 0,
		int    $newImageQuality = 90,
		bool   $cut = false,
		bool   $keepOriginal = true
	): ImageResizerResult {
		$destinationPath = $destinationDirectory . 'orig_' . $newImageName . '.' . $fileExtension;
		if ($keepOriginal) {
			copy(from: $temporaryPath, to: $destinationPath);
		} else {
			$destinationPath = $temporaryPath;
		}
		if (!file_exists(filename: $destinationPath)) {
			return ImageResizerResult::MISSING_SOURCE_IMAGE;
		}
		$originalImageSize = getimagesize(filename: $destinationPath);
		$originalWidth = (int)$originalImageSize[0];
		$originalHeight = (int)$originalImageSize[1];

		$destinationPositionX = 0;
		$destinationPositionY = 0;

		$sourcePositionX = 0;
		$sourcePositionY = 0;

		$resizeFactor = 1;

		if ($originalWidth < $thumbnailWidth) {
			$thumbnailWidth = $originalWidth;
		}
		if ($originalHeight < $thumbnailHeight) {
			$thumbnailHeight = $originalHeight;
		}

		if ($thumbnailWidth === 0) {
			if ($thumbnailHeight > 0 && $originalHeight > $thumbnailHeight) {
				$newWidth = (int)($originalWidth * $thumbnailHeight / $originalHeight);
				$thumbnailWidth = $newWidth;
			} else {
				$thumbnailWidth = $originalWidth;
			}
		}

		if ($thumbnailHeight === 0) {
			if ($thumbnailWidth > 0 && $originalWidth > $thumbnailWidth) {
				$newHeight = (int)($originalHeight * $thumbnailWidth / $originalWidth);
				$thumbnailHeight = $newHeight;
			} else {
				$thumbnailHeight = $originalHeight;
			}
		}

		if ($originalWidth > $thumbnailWidth && $originalHeight > $thumbnailHeight) {
			$factorWidth = $thumbnailWidth / $originalWidth;
			$factorHeight = $thumbnailHeight / $originalHeight;
			if ($factorWidth > $factorHeight) {
				$resizeFactor = ($cut) ? $factorWidth : $factorHeight;
			} else {
				$resizeFactor = ($cut) ? $factorHeight : $factorWidth;
			}
			$newWidth = (int)round(num: $originalWidth * $resizeFactor);
			$newHeight = (int)round(num: $originalHeight * $resizeFactor);
		} else {
			$newWidth = $originalWidth;
			$newHeight = $originalHeight;
		}

		if ($newWidth > $thumbnailWidth) {
			$sourcePositionX = (int)round(num: ($newWidth - $thumbnailWidth) / 2 / $resizeFactor);
		} else if ($newWidth < $thumbnailWidth) {
			$destinationPositionX = (int)round(num: ($thumbnailWidth - $newWidth) / 2);
		}

		if ($newHeight > $thumbnailHeight) {
			$sourcePositionY = (int)round(num: ($newHeight - $thumbnailHeight) / 2 / $resizeFactor);
		} else if ($newHeight < $thumbnailHeight) {
			$destinationPositionY = (int)round(num: ($thumbnailHeight - $newHeight) / 2);
		}

		$lcFileExtension = strtolower(string: $fileExtension);
		try {
			$originalImage = match ($lcFileExtension) {
				'gif' => imagecreatefromgif(filename: $destinationPath),
				'jpg', 'jpeg' => imagecreatefromjpeg(filename: $destinationPath),
				'png' => imagecreatefrompng(filename: $destinationPath),
				default => imagecreate(width: 100, height: 100),
			};
		} catch (Throwable) {
			$originalImage = false;
		}
		if ($originalImage === false) {
			return ImageResizerResult::CREATE_ORIGINAL_FAILED;
		}
		$thumbnailImage = match ($lcFileExtension) {
			'jpg', 'jpeg', 'png' => imagecreatetruecolor(width: $thumbnailWidth, height: $thumbnailHeight),
			default => imagecreate(width: $thumbnailWidth, height: $thumbnailHeight),
		};
		$backgroundColor = imagecolorallocate(image: $thumbnailImage, red: 255, green: 255, blue: 255);
		ImageFilledRectangle(
			image: $thumbnailImage,
			x1: 0,
			y1: 0,
			x2: $thumbnailWidth,
			y2: $thumbnailHeight,
			color: $backgroundColor
		);
		imagecopyresampled(
			dst_image: $thumbnailImage,
			src_image: $originalImage,
			dst_x: $destinationPositionX,
			dst_y: $destinationPositionY,
			src_x: $sourcePositionX,
			src_y: $sourcePositionY,
			dst_width: $newWidth,
			dst_height: $newHeight,
			src_width: $originalWidth,
			src_height: $originalHeight
		);
		imagedestroy(image: $originalImage);

		$newImage = $destinationDirectory . $newImageName . '.' . $fileExtension;
		switch (strtolower(string: $fileExtension)) {
			case 'gif':
				imagegif(image: $thumbnailImage, file: $newImage);
				break;

			case 'jpg':
			case 'jpeg':
				imagejpeg(image: $thumbnailImage, file: $newImage, quality: $newImageQuality);
				break;

			case 'png':
				imagepng(image: $thumbnailImage, file: $newImage);
				break;
		}
		imagedestroy(image: $thumbnailImage);

		return file_exists(filename: $newImage) ? ImageResizerResult::SUCCESS : ImageResizerResult::CREATE_NEW_FAILED;
	}
}