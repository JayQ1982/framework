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
	  'NationalNumberPattern' => '(?:[1-4]|[5-79]\\d|80)\\d{7}',
	  'PossibleLength' =>
    [
      0 => 8,
      1 => 9,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'fixedLine' =>
  [
	  'NationalNumberPattern' => '9619\\d{5}|(?:1\\d|2[013-79]|3[0-8]|4[0135689])\\d{6}',
	  'ExampleNumber' => '12345678',
	  'PossibleLength' =>
    [
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'mobile' =>
  [
	  'NationalNumberPattern' => '67[0-6]\\d{6}|(?:5[4-6]|6[569]|7[7-9])\\d{7}',
	  'ExampleNumber' => '551234567',
	  'PossibleLength' =>
    [
      0 => 9,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'tollFree' =>
  [
	  'NationalNumberPattern' => '800\\d{6}',
	  'ExampleNumber' => '800123456',
	  'PossibleLength' =>
    [
      0 => 9,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'premiumRate' =>
  [
	  'NationalNumberPattern' => '80[3-689]1\\d{5}',
	  'ExampleNumber' => '808123456',
	  'PossibleLength' =>
    [
      0 => 9,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'sharedCost' =>
  [
	  'NationalNumberPattern' => '80[12]1\\d{5}',
	  'ExampleNumber' => '801123456',
	  'PossibleLength' =>
    [
      0 => 9,
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
	  'NationalNumberPattern' => '98[23]\\d{6}',
	  'ExampleNumber' => '983123456',
	  'PossibleLength' =>
    [
      0 => 9,
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
	'id' => 'DZ',
	'countryCode' => 213,
	'internationalPrefix' => '00',
	'nationalPrefix' => '0',
	'nationalPrefixForParsing' => '0',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat' =>
  [
	  0 =>
    [
	    'pattern' => '(\\d{2})(\\d{2})(\\d{2})(\\d{2})',
	    'format' => '$1 $2 $3 $4',
	    'leadingDigitsPatterns' =>
      [
        0 => '[1-4]',
      ],
	    'nationalPrefixFormattingRule' => '0$1',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  1 =>
    [
	    'pattern' => '(\\d{2})(\\d{3})(\\d{2})(\\d{2})',
	    'format' => '$1 $2 $3 $4',
	    'leadingDigitsPatterns' =>
      [
        0 => '9',
      ],
	    'nationalPrefixFormattingRule' => '0$1',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  2 =>
    [
	    'pattern' => '(\\d{3})(\\d{2})(\\d{2})(\\d{2})',
	    'format' => '$1 $2 $3 $4',
	    'leadingDigitsPatterns' =>
      [
        0 => '[5-8]',
      ],
	    'nationalPrefixFormattingRule' => '0$1',
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
