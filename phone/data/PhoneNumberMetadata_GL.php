<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

// Adapted from https://github.com/google/libphonenumber
return [
	'generalDesc'                   =>
		[
			'NationalNumberPattern'   => '(?:19|[2-689]\\d)\\d{4}',
			'PossibleLength'          =>
				[
					0 => 6,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'fixedLine'                     =>
		[
			'NationalNumberPattern'   => '(?:19|3[1-7]|6[14689]|8[14-79]|9\\d)\\d{4}',
			'ExampleNumber'           => '321000',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'mobile'                        =>
		[
			'NationalNumberPattern'   => '(?:[25][1-9]|4[2-9])\\d{4}',
			'ExampleNumber'           => '221234',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'tollFree'                      =>
		[
			'NationalNumberPattern'   => '80\\d{4}',
			'ExampleNumber'           => '801234',
			'PossibleLength'          =>
				[
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
			'NationalNumberPattern'   => '3[89]\\d{4}',
			'ExampleNumber'           => '381234',
			'PossibleLength'          =>
				[
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
	'id'                            => 'GL',
	'countryCode'                   => 299,
	'internationalPrefix'           => '00',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat'                  =>
		[
			0 =>
				[
					'pattern'                              => '(\\d{2})(\\d{2})(\\d{2})',
					'format'                               => '$1 $2 $3',
					'leadingDigitsPatterns'                =>
						[
							0 => '19|[2-689]',
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
