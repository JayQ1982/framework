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
	  'NationalNumberPattern' => '8000\\d{3}|(?:[249]\\d|64)\\d{5}',
	  'PossibleLength' =>
    [
      0 => 7,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'fixedLine' =>
  [
	  'NationalNumberPattern' => '4[2-46]\\d{5}',
	  'ExampleNumber' => '4217123',
	  'PossibleLength' =>
    [
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'mobile' =>
  [
	  'NationalNumberPattern' => '2[5-8]\\d{5}',
	  'ExampleNumber' => '2510123',
	  'PossibleLength' =>
    [
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'tollFree' =>
  [
	  'NationalNumberPattern' => '8000\\d{3}',
	  'ExampleNumber' => '8000000',
	  'PossibleLength' =>
    [
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
	'sharedCost' =>
  [
	  'PossibleLength' =>
    [
      0 => -1,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'personalNumber' =>
  [
	  'PossibleLength' =>
    [
      0 => -1,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'voip' =>
  [
	  'NationalNumberPattern' => '971\\d{4}|(?:64|95)\\d{5}',
	  'ExampleNumber' => '6412345',
	  'PossibleLength' =>
    [
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
	  'PossibleLength' =>
    [
      0 => -1,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'voicemail' =>
  [
	  'PossibleLength' =>
    [
      0 => -1,
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
	'id' => 'SC',
	'countryCode' => 248,
	'internationalPrefix' => '010|0[0-2]',
	'preferredInternationalPrefix' => '00',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat' =>
  [
	  0 =>
    [
	    'pattern' => '(\\d)(\\d{3})(\\d{3})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '[246]',
      ],
	    'nationalPrefixFormattingRule' => '',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
  ],
	'intlNumberFormat' =>
  [
  ],
	'mainCountryForCode' => false,
	'leadingZeroPossible' => false,
	'mobileNumberPortableRegion' => false,
];
