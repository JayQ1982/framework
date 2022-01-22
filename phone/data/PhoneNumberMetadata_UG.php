<?php
/**
 * @author    Christof Moser <christof.moser@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

// Adapted from https://github.com/google/libphonenumber
return [
	'generalDesc'                   =>
		[
			'NationalNumberPattern'   => '800\\d{6}|(?:[29]0|[347]\\d)\\d{7}',
			'PossibleLength'          =>
				[
					0 => 9,
				],
			'PossibleLengthLocalOnly' =>
				[
					0 => 5,
					1 => 6,
					2 => 7,
				],
		],
	'fixedLine'                     =>
		[
			'NationalNumberPattern'   => '(?:20(?:(?:(?:[0147]\\d|5[0-4])\\d|2(?:40|[5-9]\\d)|3(?:0[67]|2[0-4])|810)\\d|6(?:00[0-2]|[15-9]\\d\\d|30[0-4]))|[34]\\d{5})\\d{3}',
			'ExampleNumber'           => '312345678',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
					0 => 5,
					1 => 6,
					2 => 7,
				],
		],
	'mobile'                        =>
		[
			'NationalNumberPattern'   => '7260\\d{5}|7(?:[0157-9]\\d|20|4[0-4])\\d{6}',
			'ExampleNumber'           => '712345678',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'tollFree'                      =>
		[
			'NationalNumberPattern'   => '800[1-3]\\d{5}',
			'ExampleNumber'           => '800123456',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'premiumRate'                   =>
		[
			'NationalNumberPattern'   => '90[1-3]\\d{6}',
			'ExampleNumber'           => '901123456',
			'PossibleLength'          =>
				[
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
	'id'                            => 'UG',
	'countryCode'                   => 256,
	'internationalPrefix'           => '00[057]',
	'nationalPrefix'                => '0',
	'nationalPrefixForParsing'      => '0',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat'                  =>
		[
			0 =>
				[
					'pattern'                              => '(\\d{4})(\\d{5})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '202',
							1 => '2024',
						],
					'nationalPrefixFormattingRule'         => '0$1',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			1 =>
				[
					'pattern'                              => '(\\d{3})(\\d{6})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '[27-9]|4(?:6[45]|[7-9])',
						],
					'nationalPrefixFormattingRule'         => '0$1',
					'domesticCarrierCodeFormattingRule'    => '',
					'nationalPrefixOptionalWhenFormatting' => false,
				],
			2 =>
				[
					'pattern'                              => '(\\d{2})(\\d{7})',
					'format'                               => '$1 $2',
					'leadingDigitsPatterns'                =>
						[
							0 => '[34]',
						],
					'nationalPrefixFormattingRule'         => '0$1',
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
