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
	  'NationalNumberPattern' => '0\\d{4}|(?:[4589]\\d|79)\\d{6}',
	  'PossibleLength' =>
    [
      0 => 5,
      1 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'fixedLine' =>
  [
	  'NationalNumberPattern' => '79\\d{6}',
	  'ExampleNumber' => '79123456',
	  'PossibleLength' =>
    [
      0 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'mobile' =>
  [
	  'NationalNumberPattern' => '(?:4[015-8]|5[89]|9\\d)\\d{6}',
	  'ExampleNumber' => '41234567',
	  'PossibleLength' =>
    [
      0 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'tollFree' =>
  [
	  'NationalNumberPattern' => '80[01]\\d{5}',
	  'ExampleNumber' => '80012345',
	  'PossibleLength' =>
    [
      0 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'premiumRate' =>
  [
	  'NationalNumberPattern' => '82[09]\\d{5}',
	  'ExampleNumber' => '82012345',
	  'PossibleLength' =>
    [
      0 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'sharedCost' =>
  [
	  'NationalNumberPattern' => '810(?:0[0-6]|[2-8]\\d)\\d{3}',
	  'ExampleNumber' => '81021234',
	  'PossibleLength' =>
    [
      0 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'personalNumber' =>
  [
	  'NationalNumberPattern' => '880\\d{5}',
	  'ExampleNumber' => '88012345',
	  'PossibleLength' =>
    [
      0 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'voip' =>
  [
	  'NationalNumberPattern' => '85[0-5]\\d{5}',
	  'ExampleNumber' => '85012345',
	  'PossibleLength' =>
    [
      0 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'pager' =>
  [
	  'PossibleLength' =>
    [
      0 => -1,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'uan' =>
  [
	  'NationalNumberPattern' => '(?:0[2-9]|81(?:0(?:0[7-9]|1\\d)|5\\d\\d))\\d{3}',
	  'ExampleNumber' => '02000',
	  'PossibleLength' =>
    [
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'voicemail' =>
  [
	  'NationalNumberPattern' => '81[23]\\d{5}',
	  'ExampleNumber' => '81212345',
	  'PossibleLength' =>
    [
      0 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'noInternationalDialling' =>
  [
	  'PossibleLength' =>
    [
      0 => -1,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'id' => 'SJ',
	'countryCode' => 47,
	'internationalPrefix' => '00',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat' =>
  [
  ],
	'intlNumberFormat' =>
  [
  ],
	'mainCountryForCode' => false,
	'leadingDigits' => '79',
	'leadingZeroPossible' => false,
	'mobileNumberPortableRegion' => false,
];
