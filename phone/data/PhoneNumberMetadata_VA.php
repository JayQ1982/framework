<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 * .
 * Adapted work based on https://github.com/giggsey/libphonenumber-for-php , which was published
 * with "Apache License Version 2.0, January 2004" ( http://www.apache.org/licenses/ )
 */

use framework\phone\PhoneCountryCodes;

return [
	'generalDesc'                   =>
		[
			'NationalNumberPattern'   => '0\\d{5,10}|3[0-8]\\d{7,10}|55\\d{8}|8\\d{5}(?:\\d{2,4})?|(?:1\\d|39)\\d{7,8}',
			'PossibleLength'          =>
				[
					0 => 6,
					1 => 7,
					2 => 8,
					3 => 9,
					4 => 10,
					5 => 11,
					6 => 12,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'fixedLine'                     =>
		[
			'NationalNumberPattern'   => '06698\\d{1,6}',
			'ExampleNumber'           => '0669812345',
			'PossibleLength'          =>
				[
					0 => 6,
					1 => 7,
					2 => 8,
					3 => 9,
					4 => 10,
					5 => 11,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'mobile'                        =>
		[
			'NationalNumberPattern'   => '3[1-9]\\d{8}|3[2-9]\\d{7}',
			'ExampleNumber'           => '3123456789',
			'PossibleLength'          =>
				[
					0 => 9,
					1 => 10,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'tollFree'                      =>
		[
			'NationalNumberPattern'   => '80(?:0\\d{3}|3)\\d{3}',
			'ExampleNumber'           => '800123456',
			'PossibleLength'          =>
				[
					0 => 6,
					1 => 9,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'premiumRate'                   =>
		[
			'NationalNumberPattern'   => '(?:0878\\d\\d|89(?:2|4[5-9]\\d))\\d{3}|89[45][0-4]\\d\\d|(?:1(?:44|6[346])|89(?:5[5-9]|9))\\d{6}',
			'ExampleNumber'           => '899123456',
			'PossibleLength'          =>
				[
					0 => 6,
					1 => 8,
					2 => 9,
					3 => 10,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'sharedCost'                    =>
		[
			'NationalNumberPattern'   => '84(?:[08]\\d{3}|[17])\\d{3}',
			'ExampleNumber'           => '848123456',
			'PossibleLength'          =>
				[
					0 => 6,
					1 => 9,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'personalNumber'                =>
		[
			'NationalNumberPattern'   => '1(?:78\\d|99)\\d{6}',
			'ExampleNumber'           => '1781234567',
			'PossibleLength'          =>
				[
					0 => 9,
					1 => 10,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'voip'                          =>
		[
			'NationalNumberPattern'   => '55\\d{8}',
			'ExampleNumber'           => '5512345678',
			'PossibleLength'          =>
				[
					0 => 10,
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
			'NationalNumberPattern'   => '3[2-8]\\d{9,10}',
			'ExampleNumber'           => '33101234501',
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
			'PossibleLength'          =>
				[
					0 => -1,
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'id'                            => 'VA',
	'countryCode'                   => PhoneCountryCodes::IT,
	'internationalPrefix'           => '00',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat'                  =>
		[
		],
	'intlNumberFormat'              =>
		[
		],
	'mainCountryForCode'            => false,
	'leadingDigits'                 => '06698',
	'leadingZeroPossible'           => false,
	'mobileNumberPortableRegion'    => true,
];