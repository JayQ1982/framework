<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

return [
	'generalDesc'                   =>
		[
			'NationalNumberPattern'   => '1\\d{6}(?:\\d{3,5})?|[57]\\d{8}|[1-489]\\d{7}',
			'PossibleLength'          =>
				[
					0 => 7,
					1 => 8,
					2 => 9,
					3 => 10,
					4 => 11,
					5 => 12,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'fixedLine'                     =>
		[
			'NationalNumberPattern'   => '153\\d{8,9}|[2-489]\\d{7}',
			'ExampleNumber'           => '21234567',
			'PossibleLength'          =>
				[
					0 => 8,
					1 => 11,
					2 => 12,
				],
			'PossibleLengthLocalOnly' =>
				[
					0 => 7,
				],
		],
	'mobile'                        =>
		[
			'NationalNumberPattern'   => '5(?:(?:[0-389][2-9]|4[1-9]|6\\d)\\d|5(?:01|2[2-6]|3[23]|4[45]|5[05689]|6[6-8]|7[0-267]|8[7-9]|9[1-9]))\\d{5}',
			'ExampleNumber'           => '502345678',
			'PossibleLength'          =>
				[
					0 => 9,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'tollFree'                      =>
		[
			'NationalNumberPattern'   => '1(?:255|80[019]\\d{3})\\d{3}',
			'ExampleNumber'           => '1800123456',
			'PossibleLength'          =>
				[
					0 => 7,
					1 => 10,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'premiumRate'                   =>
		[
			'NationalNumberPattern'   => '1212\\d{4}|1(?:200|9(?:0[01]|19))\\d{6}',
			'ExampleNumber'           => '1919123456',
			'PossibleLength'          =>
				[
					0 => 8,
					1 => 10,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'sharedCost'                    =>
		[
			'NationalNumberPattern'   => '1700\\d{6}',
			'ExampleNumber'           => '1700123456',
			'PossibleLength'          =>
				[
					0 => 10,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'personalNumber'                =>
		[
			'PossibleLength'          =>
				[
					0 => -1,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'voip'                          =>
		[
			'NationalNumberPattern'   => '78(?:33|55|77|81)\\d{5}|7(?:18|2[23]|3[237]|47|6[58]|7\\d|82|9[235-9])\\d{6}',
			'ExampleNumber'           => '771234567',
			'PossibleLength'          =>
				[
					0 => 9,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'pager'                         =>
		[
			'PossibleLength'          =>
				[
					0 => -1,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'uan'                           =>
		[
			'NationalNumberPattern'   => '1599\\d{6}',
			'ExampleNumber'           => '1599123456',
			'PossibleLength'          =>
				[
					0 => 10,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'voicemail'                     =>
		[
			'NationalNumberPattern'   => '151\\d{8,9}',
			'ExampleNumber'           => '15112340000',
			'PossibleLength'          =>
				[
					0 => 11,
					1 => 12,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'noInternationalDialling'       =>
		[
			'NationalNumberPattern'   => '1700\\d{6}',
			'PossibleLength'          =>
				[
					0 => 10,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'id'                            => 'IL',
	'countryCode'                   => 972,
	'internationalPrefix'           => '0(?:0|1[2-9])',
	'nationalPrefix'                => '0',
	'nationalPrefixForParsing'      => '0',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat'                  =>
		[
			0 =>
				[
					'pattern'                              => '(\\d{4})(\\d{3})',
					'format'                               => '$1-$2',
					'leadingDigitsPatterns'                =>
						[
							0 => '125',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			1 =>
				[
					'pattern'                              => '(\\d{4})(\\d{2})(\\d{2})',
					'format'                               => '$1-$2-$3',
					'leadingDigitsPatterns'                =>
						[
							0 => '121',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			2 =>
				[
					'pattern'                              => '(\\d)(\\d{3})(\\d{4})',
					'format'                               => '$1-$2-$3',
					'leadingDigitsPatterns'                =>
						[
							0 => '[2-489]',
						],
					'nationalPrefixFormattingRule'         => '0$1',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			3 =>
				[
					'pattern'                              => '(\\d{2})(\\d{3})(\\d{4})',
					'format'                               => '$1-$2-$3',
					'leadingDigitsPatterns'                =>
						[
							0 => '[57]',
						],
					'nationalPrefixFormattingRule'         => '0$1',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			4 =>
				[
					'pattern'                              => '(\\d{4})(\\d{3})(\\d{3})',
					'format'                               => '$1-$2-$3',
					'leadingDigitsPatterns'                =>
						[
							0 => '12',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			5 =>
				[
					'pattern'                              => '(\\d{4})(\\d{6})',
					'format'                               => '$1-$2',
					'leadingDigitsPatterns'                =>
						[
							0 => '159',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			6 =>
				[
					'pattern'                              => '(\\d)(\\d{3})(\\d{3})(\\d{3})',
					'format'                               => '$1-$2-$3-$4',
					'leadingDigitsPatterns'                =>
						[
							0 => '1[7-9]',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			7 =>
				[
					'pattern'                              => '(\\d{3})(\\d{1,2})(\\d{3})(\\d{4})',
					'format'                               => '$1-$2 $3-$4',
					'leadingDigitsPatterns'                =>
						[
							0 => '15',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
		],
	'intlNumberFormat'              =>
		[
		],
	'mainCountryForCode'            => false,
	'leadingZeroPossible'           => false,
	'mobileNumberPortableRegion'    => true,
];