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
	  'NationalNumberPattern' => '(?:[25]\\d\\d|606)\\d{5}',
	  'PossibleLength' =>
    [
      0 => 8,
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'fixedLine' =>
  [
	  'NationalNumberPattern' => '21(?:6[24-7]\\d|90[0-2])\\d{3}|2(?:00|2[25])\\d{5}',
	  'ExampleNumber' => '20012345',
	  'PossibleLength' =>
    [
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'mobile' =>
  [
	  'NationalNumberPattern' => '(?:5[146-8]\\d|606)\\d{5}',
	  'ExampleNumber' => '57123456',
	  'PossibleLength' =>
    [
    ],
	  'PossibleLengthLocalOnly' =>
    [
    ],
  ],
	'tollFree' =>
  [
	  'PossibleLength' =>
    [
      0 => -1,
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
	  'PossibleLength' =>
    [
      0 => -1,
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
	'id' => 'GI',
	'countryCode' => 350,
	'internationalPrefix' => '00',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat' =>
  [
	  0 =>
    [
	    'pattern' => '(\\d{3})(\\d{5})',
	    'format' => '$1 $2',
	    'leadingDigitsPatterns' =>
      [
        0 => '2',
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
	'mobileNumberPortableRegion' => true,
];
