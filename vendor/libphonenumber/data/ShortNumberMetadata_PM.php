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
	  'NationalNumberPattern' => '[13]\\d(?:\\d{2})?',
	  'PossibleLength' =>
    [
      0 => 2,
      1 => 4,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'tollFree' =>
  [
	  'NationalNumberPattern' => '1[578]',
	  'ExampleNumber' => '15',
	  'PossibleLength' =>
    [
      0 => 2,
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
	  'NationalNumberPattern' => '1[578]',
	  'ExampleNumber' => '15',
	  'PossibleLength' =>
    [
      0 => 2,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'shortCode' =>
  [
	  'NationalNumberPattern' => '1[578]|3103',
	  'ExampleNumber' => '15',
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
	  'NationalNumberPattern' => '310\\d',
	  'ExampleNumber' => '3100',
	  'PossibleLength' =>
    [
      0 => 4,
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
	'id' => 'PM',
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
