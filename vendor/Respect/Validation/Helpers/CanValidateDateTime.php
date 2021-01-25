<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

declare(strict_types=1);

namespace framework\vendor\Respect\Validation\Helpers;

use function checkdate;
use function date_parse_from_format;
use function preg_match;

/**
 * Helper to handle date/time.
 *
 * @author Henrique Moody <henriquemoody@gmail.com>
 */
trait CanValidateDateTime
{
	/**
	 * Finds whether a value is a valid date/time in a specific format.
	 *
	 * @param string $format
	 * @param string $value
	 *
	 * @return bool
	 */
    private function isDateTime(string $format, string $value): bool
    {
        $exceptionalFormats = [
            'c' => 'Y-m-d\TH:i:sP',
            'r' => 'D, d M Y H:i:s O',
        ];

        $info = date_parse_from_format($exceptionalFormats[$format] ?? $format, $value);

        if (!$this->isDateTimeParsable($info)) {
            return false;
        }

        if ($this->isDateFormat($format)) {
            return $this->isDateInformation($info);
        }

        return true;
    }

	/**
	 * @param int[] $info
	 *
	 * @return bool
	 * @return bool
	 */
    private function isDateTimeParsable(array $info): bool
    {
        return $info['error_count'] === 0 && $info['warning_count'] === 0;
    }

    private function isDateFormat(string $format): bool
    {
        return preg_match('/[djSFmMnYy]/', $format) > 0;
    }

	/**
	 * @param mixed[] $info
	 *
	 * @return bool
	 * @return bool
	 */
    private function isDateInformation(array $info): bool
    {
        if ($info['day']) {
            return checkdate((int) $info['month'], $info['day'], (int) $info['year']);
        }

        return checkdate($info['month'] ?: 1, $info['day'] ?: 1, $info['year'] ?: 1);
    }
}
