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
	  'NationalNumberPattern' => '1(?:[0-79]\\d{7,8}|8[0-24-9]\\d{7})|[2-478]\\d{8}|1\\d{4,7}',
	  'PossibleLength' =>
    [
      0 => 5,
      1 => 6,
      2 => 7,
      3 => 8,
      4 => 9,
      5 => 10,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'fixedLine' =>
  [
	  'NationalNumberPattern' => '8(?:51(?:0(?:0[03-9]|[12479]\\d|3[2-9]|5[0-8]|6[1-9]|8[0-7])|1(?:[0235689]\\d|1[0-69]|4[0-589]|7[0-47-9])|2(?:0[0-7]|3[2-4]|[4-6]\\d))|91(?:[0-57-9]\\d|6[0135-9])\\d)\\d{3}|(?:2(?:[0-26-9]\\d|3[0-8]|4[02-9]|5[0135-9])|3(?:[0-3589]\\d|4[0-578]|6[1-9]|7[0-35-9])|7(?:[013-57-9]\\d|2[0-8])|8(?:6[0-8]|[78]\\d|9[02-9]))\\d{6}',
	  'ExampleNumber' => '212345678',
	  'PossibleLength' =>
    [
      0 => 9,
    ],
	  'PossibleLengthLocalOnly' =>
    [
      0 => 8,
    ],
  ],
	'mobile' =>
  [
	  'NationalNumberPattern' => '4(?:83[0-38]|93[0-4])\\d{5}|4(?:[0-3]\\d|4[047-9]|5[0-25-9]|6[06-9]|7[02-9]|8[0-24-9]|9[0-27-9])\\d{6}',
	  'ExampleNumber' => '412345678',
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
	  'NationalNumberPattern' => '180(?:0\\d{3}|2)\\d{3}',
	  'ExampleNumber' => '1800123456',
	  'PossibleLength' =>
    [
      0 => 7,
      1 => 10,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'premiumRate' =>
  [
	  'NationalNumberPattern' => '190[0-26]\\d{6}',
	  'ExampleNumber' => '1900123456',
	  'PossibleLength' =>
    [
      0 => 10,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'sharedCost' =>
  [
	  'NationalNumberPattern' => '13(?:00\\d{3}|45[0-4])\\d{3}|13\\d{4}',
	  'ExampleNumber' => '1300123456',
	  'PossibleLength' =>
    [
      0 => 6,
      1 => 8,
      2 => 10,
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
	  'NationalNumberPattern' => '14(?:5(?:1[0458]|[23][458])|71\\d)\\d{4}',
	  'ExampleNumber' => '147101234',
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
	  'NationalNumberPattern' => '163\\d{2,6}',
	  'ExampleNumber' => '1631234',
	  'PossibleLength' =>
    [
      0 => 5,
      1 => 6,
      2 => 7,
      3 => 8,
      4 => 9,
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
	  'NationalNumberPattern' => '1[38]00\\d{6}|1(?:345[0-4]|802)\\d{3}|13\\d{4}',
	  'PossibleLength' =>
    [
      0 => 6,
      1 => 7,
      2 => 8,
      3 => 10,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'id' => 'AU',
	'countryCode' => 61,
	'internationalPrefix' => '001[14-689]|14(?:1[14]|34|4[17]|[56]6|7[47]|88)0011',
	'preferredInternationalPrefix' => '0011',
	'nationalPrefix' => '0',
	'nationalPrefixForParsing' => '0|(183[12])',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat' =>
  [
	  0 =>
    [
	    'pattern' => '(\\d{2})(\\d{3,4})',
	    'format' => '$1 $2',
	    'leadingDigitsPatterns' =>
      [
        0 => '16',
      ],
	    'nationalPrefixFormattingRule' => '0$1',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  1 =>
    [
	    'pattern' => '(\\d{2})(\\d{2})(\\d{2})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '13',
      ],
	    'nationalPrefixFormattingRule' => '',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  2 =>
    [
	    'pattern' => '(\\d{3})(\\d{3})',
	    'format' => '$1 $2',
	    'leadingDigitsPatterns' =>
      [
        0 => '19',
      ],
	    'nationalPrefixFormattingRule' => '',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  3 =>
    [
	    'pattern' => '(\\d{3})(\\d{4})',
	    'format' => '$1 $2',
	    'leadingDigitsPatterns' =>
      [
        0 => '180',
        1 => '1802',
      ],
	    'nationalPrefixFormattingRule' => '',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  4 =>
    [
	    'pattern' => '(\\d{4})(\\d{3,4})',
	    'format' => '$1 $2',
	    'leadingDigitsPatterns' =>
      [
        0 => '19',
      ],
	    'nationalPrefixFormattingRule' => '',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  5 =>
    [
	    'pattern' => '(\\d{2})(\\d{3})(\\d{2,4})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '16',
      ],
	    'nationalPrefixFormattingRule' => '0$1',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  6 =>
    [
	    'pattern' => '(\\d{3})(\\d{3})(\\d{3})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '14|4',
      ],
	    'nationalPrefixFormattingRule' => '0$1',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  7 =>
    [
	    'pattern' => '(\\d)(\\d{4})(\\d{4})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '[2378]',
      ],
	    'nationalPrefixFormattingRule' => '(0$1)',
	    'domesticCarrierCodeFormattingRule' => '$CC ($1)',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  8 =>
    [
	    'pattern' => '(\\d{4})(\\d{3})(\\d{3})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '1(?:30|[89])',
      ],
	    'nationalPrefixFormattingRule' => '',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
  ],
	'intlNumberFormat' =>
  [
	  0 =>
    [
	    'pattern' => '(\\d{2})(\\d{3,4})',
	    'format' => '$1 $2',
	    'leadingDigitsPatterns' =>
      [
        0 => '16',
      ],
	    'nationalPrefixFormattingRule' => '0$1',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  1 =>
    [
	    'pattern' => '(\\d{2})(\\d{3})(\\d{2,4})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '16',
      ],
	    'nationalPrefixFormattingRule' => '0$1',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  2 =>
    [
	    'pattern' => '(\\d{3})(\\d{3})(\\d{3})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '14|4',
      ],
	    'nationalPrefixFormattingRule' => '0$1',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  3 =>
    [
	    'pattern' => '(\\d)(\\d{4})(\\d{4})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '[2378]',
      ],
	    'nationalPrefixFormattingRule' => '(0$1)',
	    'domesticCarrierCodeFormattingRule' => '$CC ($1)',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  4 =>
    [
	    'pattern' => '(\\d{4})(\\d{3})(\\d{3})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '1(?:30|[89])',
      ],
	    'nationalPrefixFormattingRule' => '',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
  ],
	'mainCountryForCode' => true,
	'leadingZeroPossible' => false,
	'mobileNumberPortableRegion' => true,
];
