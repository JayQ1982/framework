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
	  'NationalNumberPattern' => '[1-9]\\d\\d(?:\\d\\d(?:\\d(?:\\d{2})?)?)?',
	  'PossibleLength' =>
    [
      0 => 3,
      1 => 5,
      2 => 6,
      3 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'tollFree' =>
  [
	  'NationalNumberPattern' => '112|[29]11',
	  'ExampleNumber' => '112',
	  'PossibleLength' =>
    [
      0 => 3,
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
	  'NationalNumberPattern' => '112|911',
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
	  'NationalNumberPattern' => '112|30000\\d{3}|[1-35-9]\\d{4,5}|[2-9]11',
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
	  'NationalNumberPattern' => '[235-7]11',
	  'ExampleNumber' => '211',
	  'PossibleLength' =>
    [
      0 => 3,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'smsServices' =>
  [
	  'NationalNumberPattern' => '300\\d{5}|[1-35-9]\\d{4,5}',
	  'ExampleNumber' => '10000',
	  'PossibleLength' =>
    [
      0 => 5,
      1 => 6,
      2 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'id' => 'CA',
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
