<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

// Adapted from https://github.com/google/libphonenumber
return [
	'generalDesc'                   =>
		[
			'NationalNumberPattern'   => '(?:28|[68]\\d)\\d{6}',
			'PossibleLength'          =>
				[
					0 => 8,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'fixedLine'                     =>
		[
			'NationalNumberPattern'   => '(?:28[2-57-9]|8(?:11|[2-57-9]\\d))\\d{5}',
			'ExampleNumber'           => '28212345',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'mobile'                        =>
		[
			'NationalNumberPattern'   => '6(?:[2356]\\d\\d|8(?:[02][5-9]|[1478]\\d|[356][0-4]))\\d{4}',
			'ExampleNumber'           => '66123456',
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
	'id'                            => 'MO',
	'countryCode'                   => 853,
	'internationalPrefix'           => '00',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat'                  =>
		[
			0 =>
				[
					'pattern'                              => '(\\d{4})(\\d{4})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '[268]',
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
