<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\common;

class ImageResizer
{
	public function resize(
		string $tmp_name,
		string $fileExtension,
		string $destinationDirectory = '',
		string $newImageName = '',
		int    $thumbnailWith = 0,
		int    $thumbnailHeight = 0,
		int    $newImageQuality = 90,
		bool   $cut = false,
		bool   $keepOriginal = true
	): ImageResizerResult {
		if (!is_dir(filename: $destinationDirectory)) {
			mkdir(directory: $destinationDirectory);
		}
		$destinationPath = $destinationDirectory . 'orig_' . $newImageName . '.' . $fileExtension;
		if ($keepOriginal) {
			copy(from: $tmp_name, to: $destinationPath);
		} else {
			$destinationPath = $tmp_name;
		}
		if (!file_exists(filename: $destinationPath)) {
			return ImageResizerResult::FAILED_TO_CREATE_DESTINATION_FILE;
		}
		$originalImageSize = getimagesize(filename: $destinationPath);
		$originalWith = (int)$originalImageSize[0];
		$originalHeight = (int)$originalImageSize[1];

		$destinationPositionX = 0;
		$destinationPositionY = 0;

		$sourcePositionX = 0;
		$sourcePositionY = 0;

		$factor = 1;

		if ($originalWith < $thumbnailWith) {
			$thumbnailWith = $originalWith;
		}
		if ($originalHeight < $thumbnailHeight) {
			$thumbnailHeight = $originalHeight;
		}

		if ($thumbnailWith === 0) {
			if ($thumbnailHeight > 0 && $originalHeight > $thumbnailHeight) {
				$newWidth = $originalWith * $thumbnailHeight / $originalHeight;
				$thumbnailWith = $newWidth;
			} else {
				$thumbnailWith = $originalWith;
			}
		}

		if ($thumbnailHeight === 0) {
			if ($thumbnailWith > 0 && $originalWith > $thumbnailWith) {
				$newHeight = $originalHeight * $thumbnailWith / $originalWith;
				$thumbnailHeight = $newHeight;
			} else {
				$thumbnailHeight = $originalHeight;
			}
		}

		if ($originalWith > $thumbnailWith && $originalHeight > $thumbnailHeight) {
			$factorWith = $thumbnailWith / $originalWith;
			$factorHeight = $thumbnailHeight / $originalHeight;
			if ($factorWith > $factorHeight) {
				$factor = ($cut) ? $factorWith : $factorHeight;
			} else {
				$factor = ($cut) ? $factorHeight : $factorWith;
			}
			$newWidth = (int)round(num: $originalWith * $factor);
			$newHeight = (int)round(num: $originalHeight * $factor);
		} else {
			$newWidth = $originalWith;
			$newHeight = $originalHeight;
		}

		if ($newWidth > $thumbnailWith) {
			$sourcePositionX = (int)round(num: ($newWidth - $thumbnailWith) / 2 / $factor);
		} else if ($newWidth < $thumbnailWith) {
			$destinationPositionX = (int)round(num: ($thumbnailWith - $newWidth) / 2);
		}

		if ($newHeight > $thumbnailHeight) {
			$sourcePositionY = (int)round(num: ($newHeight - $thumbnailHeight) / 2 / $factor);
		} else if ($newHeight < $thumbnailHeight) {
			$destinationPositionY = (int)round(num: ($thumbnailHeight - $newHeight) / 2);
		}
		switch (strtolower(string: $fileExtension)) {
			case 'gif':
				$originalImage = imagecreatefromgif(filename: $destinationPath);
				$thumbnailImage = imagecreate(width: $thumbnailWith, height: $thumbnailHeight);
				break;

			case 'jpg':
			case 'jpeg':
				$originalImage = imagecreatefromjpeg(filename: $destinationPath);
				$thumbnailImage = imagecreatetruecolor(width: $thumbnailWith, height: $thumbnailHeight);
				break;

			case 'png':
				$originalImage = imagecreatefrompng(filename: $destinationPath);
				$thumbnailImage = imagecreatetruecolor(width: $thumbnailWith, height: $thumbnailHeight);
				break;

			default:
				$originalImage = imagecreate(width: 100, height: 100);
				$thumbnailImage = imagecreate(width: $thumbnailWith, height: $thumbnailHeight);
				break;
		}
		$backgroundColor = imagecolorallocate(image: $thumbnailImage, red: 255, green: 255, blue: 255);
		ImageFilledRectangle(
			image: $thumbnailImage,
			x1: 0,
			y1: 0,
			x2: $thumbnailWith,
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
			src_width: $originalWith,
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
				imagejpeg(image: $thumbnailImage, filename: $newImage, quality: $newImageQuality);
				break;

			case 'png':
				imagepng(image: $thumbnailImage, file: $newImage);
				break;
		}
		imagedestroy(image: $thumbnailImage);

		return file_exists(filename: $newImage) ? ImageResizerResult::SUCCESS : ImageResizerResult::FAILED_TO_CREATE_NEW_IMAGE;
	}
}