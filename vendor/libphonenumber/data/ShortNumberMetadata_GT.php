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
	  'NationalNumberPattern' => '[14]\\d{2,4}',
	  'PossibleLength' =>
    [
      0 => 3,
      1 => 4,
      2 => 5,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'tollFree' =>
  [
	  'NationalNumberPattern' => '1(?:10|2[03])',
	  'ExampleNumber' => '110',
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
	  'NationalNumberPattern' => '1(?:10|2[03])',
	  'ExampleNumber' => '110',
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
	  'NationalNumberPattern' => '110|40404|1(?:2|[57]\\d)\\d',
	  'ExampleNumber' => '110',
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
	  'NationalNumberPattern' => '404\\d\\d',
	  'ExampleNumber' => '40400',
	  'PossibleLength' =>
    [
      0 => 5,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'smsServices' =>
  [
	  'NationalNumberPattern' => '404\\d\\d',
	  'ExampleNumber' => '40400',
	  'PossibleLength' =>
    [
      0 => 5,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'id' => 'GT',
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
