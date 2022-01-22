<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

// Adapted from https://github.com/google/libphonenumber
return [
	'generalDesc'                   =>
		[
			'NationalNumberPattern'   => '(?:(?:1\\d|8)\\d\\d|7000)\\d{7}|[3689]\\d{7}',
			'PossibleLength'          =>
				[
					0 => 8,
					1 => 10,
					2 => 11,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'fixedLine'                     =>
		[
			'NationalNumberPattern'   => '662[0-24-9]\\d{4}|6(?:[1-578]\\d|6[013-57-9]|9[0-35-9])\\d{5}',
			'ExampleNumber'           => '61234567',
			'PossibleLength'          =>
				[
					0 => 8,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'mobile'                        =>
		[
			'NationalNumberPattern'   => '89(?:[01]\\d|2[4-8])\\d{4}|(?:8[1-8]|9[0-8])\\d{6}',
			'ExampleNumber'           => '81234567',
			'PossibleLength'          =>
				[
					0 => 8,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'tollFree'                      =>
		[
			'NationalNumberPattern'   => '(?:18|8)00\\d{7}',
			'ExampleNumber'           => '18001234567',
			'PossibleLength'          =>
				[
					0 => 10,
					1 => 11,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'premiumRate'                   =>
		[
			'NationalNumberPattern'   => '1900\\d{7}',
			'ExampleNumber'           => '19001234567',
			'PossibleLength'          =>
				[
					0 => 11,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'sharedCost'                    =>
		[
			'PossibleLength'          =>
				[
					0 => -1,
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
			'NationalNumberPattern'   => '(?:3[12]\\d\\d|6666)\\d{4}',
			'ExampleNumber'           => '31234567',
			'PossibleLength'          =>
				[
					0 => 8,
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
			'NationalNumberPattern'   => '7000\\d{7}',
			'ExampleNumber'           => '70001234567',
			'PossibleLength'          =>
				[
					0 => 11,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'voicemail'                     =>
		[
			'PossibleLength'          =>
				[
					0 => -1,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'noInternationalDialling'       =>
		[
			'PossibleLength'          =>
				[
					0 => -1,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'id'                            => 'SG',
	'countryCode'                   => 65,
	'internationalPrefix'           => '0[0-3]\\d',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat'                  =>
		[
			0 =>
				[
					'pattern'                              => '(\\d{4,5})',
					'format'                               => '$1',
					'leadingDigitsPatterns'                =>
						[
							0 => '1[013-9]|77',
							1 => '1(?:[013-8]|9(?:0[1-9]|[1-9]))|77',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			1 =>
				[
					'pattern'                              => '(\\d{4})(\\d{4})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '[369]|8[1-9]',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			2 =>
				[
					'pattern'                              => '(\\d{3})(\\d{3})(\\d{4})',
					'format'                               => '$1 $2 $3',
					'leadingDigitsPatterns'                =>
						[
							0 => '8',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			3 =>
				[
					'pattern'                              => '(\\d{4})(\\d{4})(\\d{3})',
					'format'                               => '$1 $2 $3',
					'leadingDigitsPatterns'                =>
						[
							0 => '7',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			4 =>
				[
					'pattern'                              => '(\\d{4})(\\d{3})(\\d{4})',
					'format'                               => '$1 $2 $3',
					'leadingDigitsPatterns'                =>
						[
							0 => '1',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
		],
	'intlNumberFormat'              =>
		[
			0 =>
				[
					'pattern'                              => '(\\d{4})(\\d{4})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '[369]|8[1-9]',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			1 =>
				[
					'pattern'                              => '(\\d{3})(\\d{3})(\\d{4})',
					'format'                               => '$1 $2 $3',
					'leadingDigitsPatterns'                =>
						[
							0 => '8',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			2 =>
				[
					'pattern'                              => '(\\d{4})(\\d{4})(\\d{3})',
					'format'                               => '$1 $2 $3',
					'leadingDigitsPatterns'                =>
						[
							0 => '7',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			3 =>
				[
					'pattern'                              => '(\\d{4})(\\d{3})(\\d{4})',
					'format'                               => '$1 $2 $3',
					'leadingDigitsPatterns'                =>
						[
							0 => '1',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
		],
	'mainCountryForCode'            => false,
	'leadingZeroPossible'           => false,
	'mobileNumberPortableRegion'    => true,
];
