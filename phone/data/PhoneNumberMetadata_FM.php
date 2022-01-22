<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

// Adapted from https://github.com/google/libphonenumber
return [
	'generalDesc'                   =>
		[
			'NationalNumberPattern'   => '[39]\\d{6}',
			'PossibleLength'          =>
				[
					0 => 7,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'fixedLine'                     =>
		[
			'NationalNumberPattern'   => '(?:3[2357]0[1-9]|9[2-6]\\d\\d)\\d{3}',
			'ExampleNumber'           => '3201234',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'mobile'                        =>
		[
			'NationalNumberPattern'   => '(?:3[2357]0[1-9]|9[2-7]\\d\\d)\\d{3}',
			'ExampleNumber'           => '3501234',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'tollFree'                      =>
		[
			'PossibleLength'          =>
				[
					0 => -1,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'premiumRate'                   =>
		[
			'PossibleLength'          =>
				[
					0 => -1,
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
	'id'                            => 'FM',
	'countryCode'                   => 691,
	'internationalPrefix'           => '00',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat'                  =>
		[
			0 =>
				[
					'pattern'                              => '(\\d{3})(\\d{4})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '[39]',
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
	'mobileNumberPortableRegion'    => false,
];
