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
	'id' => '',
	'countryCode' => 94,
	'internationalPrefix' => '',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat' =>
  [
	  0 =>
    [
	    'pattern' => '(\\d{2})(\\d)(\\d{6})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '[1-689]',
      ],
	    'nationalPrefixFormattingRule' => '',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  1 =>
    [
	    'pattern' => '(\\d{3})(\\d{6})',
	    'format' => '$1 $2',
	    'leadingDigitsPatterns' =>
      [
        0 => '[1-689]',
      ],
	    'nationalPrefixFormattingRule' => '',
	    'domesticCarrierCodeFormattingRule' => '',
	    'nationalPrefixOptionalWhenFormatting' => false,
    ],
	  2 =>
    [
	    'pattern' => '(\\d{3})(\\d{3})(\\d{3})',
	    'format' => '$1 $2 $3',
	    'leadingDigitsPatterns' =>
      [
        0 => '7',
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
