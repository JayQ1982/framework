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
	  'NationalNumberPattern' => '[18]\\d\\d(?:\\d(?:\\d{2})?)?',
	  'PossibleLength' =>
    [
      0 => 3,
      1 => 4,
      2 => 6,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'tollFree' =>
  [
	  'NationalNumberPattern' => '11(?:2|6\\d{3})',
	  'ExampleNumber' => '112',
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
	  'NationalNumberPattern' => '(?:1(?:18\\d|[24])|8[48])\\d\\d',
	  'ExampleNumber' => '1200',
	  'PossibleLength' =>
    [
      0 => 4,
      1 => 6,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'emergency' =>
  [
	  'NationalNumberPattern' => '112',
	  'ExampleNumber' => '112',
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
	  'NationalNumberPattern' => '1(?:1(?:2|6111|8932)|[24]\\d\\d|9(?:21|3[02]|5[178]))|8[48]\\d\\d|11(?:60|83)00',
	  'ExampleNumber' => '112',
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
	  'PossibleLength' =>
    [
      0 => -1,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'smsServices' =>
  [
	  'NationalNumberPattern' => '(?:1[24]|8[48])\\d\\d',
	  'ExampleNumber' => '1200',
	  'PossibleLength' =>
    [
      0 => 4,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'id' => 'RO',
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
