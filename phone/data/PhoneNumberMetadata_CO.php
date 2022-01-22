<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

// Adapted from https://github.com/google/libphonenumber
return [
	'generalDesc'                   =>
		[
			'NationalNumberPattern'   => '(?:1\\d|3)\\d{9}|[124-8]\\d{7}',
			'PossibleLength'          =>
				[
					0 => 8,
					1 => 10,
					2 => 11,
				],
			'PossibleLengthLocalOnly' =>
				[
					0 => 7,
				],
		],
	'fixedLine'                     =>
		[
			'NationalNumberPattern'   => '[124-8][2-9]\\d{6}',
			'ExampleNumber'           => '12345678',
			'PossibleLength'          =>
				[
					0 => 8,
				],
			'PossibleLengthLocalOnly' =>
				[
					0 => 7,
				],
		],
	'mobile'                        =>
		[
			'NationalNumberPattern'   => '3333(?:0(?:0\\d|1[0-5])|[4-9]\\d\\d)\\d{3}|33(?:00|3[0-24-9])\\d{6}|3(?:0[0-5]|1\\d|2[0-3]|5[01]|70)\\d{7}',
			'ExampleNumber'           => '3211234567',
			'PossibleLength'          =>
				[
					0 => 10,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'tollFree'                      =>
		[
			'NationalNumberPattern'   => '1800\\d{7}',
			'ExampleNumber'           => '18001234567',
			'PossibleLength'          =>
				[
					0 => 11,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'premiumRate'                   =>
		[
			'NationalNumberPattern'   => '19(?:0[01]|4[78])\\d{7}',
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
			'PossibleLength'          =>
				[
					0 => -1,
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
			'PossibleLength'          =>
				[
					0 => -1,
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
	'id'                            => 'CO',
	'countryCode'                   => 57,
	'internationalPrefix'           => '00(?:4(?:[14]4|56)|[579])',
	'nationalPrefix'                => '0',
	'nationalPrefixForParsing'      => '0([3579]|4(?:[14]4|56))?',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat'                  =>
		[
			0 =>
				[
					'pattern'                              => '(\\d)(\\d{7})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '[14][2-9]|[25-8]',
						],
					'nationalPrefixFormattingRule'         => '($1)',
					'domesticCarrierCodeFormattingRule'    => '0$CC $1',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			1 =>
				[
					'pattern'                              => '(\\d{3})(\\d{7})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '3',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '0$CC $1',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			2 =>
				[
					'pattern'                              => '(\\d)(\\d{3})(\\d{7})',
					'format'                               => '$1-$2-$3',
					'leadingDigitsPatterns'                =>
						[
							0 => '1',
						],
					'nationalPrefixFormattingRule'         => '0$1',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
		],
	'intlNumberFormat'              =>
		[
			0 =>
				[
					'pattern'                              => '(\\d)(\\d{7})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '[14][2-9]|[25-8]',
						],
					'nationalPrefixFormattingRule'         => '($1)',
					'domesticCarrierCodeFormattingRule'    => '0$CC $1',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			1 =>
				[
					'pattern'                              => '(\\d{3})(\\d{7})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '3',
						],
					'nationalPrefixFormattingRule'         => '',
					'domesticCarrierCodeFormattingRule'    => '0$CC $1',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			2 =>
				[
					'pattern'               => '(\\d)(\\d{3})(\\d{7})',
					'format'                => '$1 $2 $3',
					'leadingDigitsPatterns' =>
						[
							0 => '1',
						],
				],
		],
	'mainCountryForCode'            => false,
	'leadingZeroPossible'           => false,
	'mobileNumberPortableRegion'    => true,
];
