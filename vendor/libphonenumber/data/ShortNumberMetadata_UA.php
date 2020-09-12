<?php
/**
 * This file has been @generated by a phing task by {@link BuildMetadataPHPFromXml}.
 * See [README.md](README.md#generating-data) for more information.
 *
 * Pull requests changing data in these files will not be accepted. See the
 * [FAQ in the README](README.md#problems-with-invalid-numbers] on how to make
 * metadata changes.
 *
 * Do not modify this file directly!
 */


return [
	'generalDesc' =>
  [
	  'NationalNumberPattern' => '[189]\\d{2,5}',
	  'PossibleLength' =>
    [
      0 => 3,
      1 => 4,
      2 => 5,
      3 => 6,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'tollFree' =>
  [
	  'NationalNumberPattern' => '1(?:0[1-3]|1(?:2|6\\d{3}))',
	  'ExampleNumber' => '101',
	  'PossibleLength' =>
    [
      0 => 3,
      1 => 6,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'premiumRate' =>
  [
	  'PossibleLength' =>
    [
      0 => -1,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'emergency' =>
  [
	  'NationalNumberPattern' => '1(?:0[1-3]|12)',
	  'ExampleNumber' => '101',
	  'PossibleLength' =>
    [
      0 => 3,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'shortCode' =>
  [
	  'NationalNumberPattern' => '1(?:0[1-49]|1(?:2|6(?:000|1(?:11|23))|8\\d\\d?)|(?:[278]|5\\d)\\d)|[89]00\\d\\d?|151|1(?:06|4\\d|6)\\d\\d',
	  'ExampleNumber' => '101',
	  'PossibleLength' =>
    [
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'standardRate' =>
  [
	  'PossibleLength' =>
    [
      0 => -1,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'carrierSpecific' =>
  [
	  'NationalNumberPattern' => '(?:118|[89]00)\\d\\d?',
	  'ExampleNumber' => '1180',
	  'PossibleLength' =>
    [
      0 => 4,
      1 => 5,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'smsServices' =>
  [
	  'PossibleLength' =>
    [
      0 => -1,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'id' => 'UA',
	'countryCode' => 0,
	'internationalPrefix' => '',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat' =>
  [
  ],
	'intlNumberFormat' =>
  [
  ],
	'mainCountryForCode' => false,
	'leadingZeroPossible' => false,
	'mobileNumberPortableRegion' => false,
];
