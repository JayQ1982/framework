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
			'NationalNumberPattern'   => '(?:[58]\\d\\d|684|900)\\d{7}',
			'PossibleLength'          =>
				[
					0 => 10,
				],
			'PossibleLengthLocalOnly' =>
				[
					0 => 7,
				],
		],
	'fixedLine'                     =>
		[
			'NationalNumberPattern'   => '6846(?:22|33|44|55|77|88|9[19])\\d{4}',
			'ExampleNumber'           => '6846221234',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
					0 => 7,
				],
		],
	'mobile'                        =>
		[
			'NationalNumberPattern'   => '684(?:2(?:5[2468]|72)|7(?:3[13]|70))\\d{4}',
			'ExampleNumber'           => '6847331234',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
					0 => 7,
				],
		],
	'tollFree'                      =>
		[
			'NationalNumberPattern'   => '8(?:00|33|44|55|66|77|88)[2-9]\\d{6}',
			'ExampleNumber'           => '8002123456',
			'PossibleLength'          =>
				[
				],
			'PossibleLengthLocalOnly' =>
				[
				],
		],
	'premiumRate'                   =>
		[
			'NationalNumberPattern'   => '900[2-9]\\d{6}',
			'ExampleNumber'           => '9002123456',
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
			'NationalNumberPattern'   => '5(?:00|2[12]|33|44|66|77|88)[2-9]\\d{6}',
			'ExampleNumber'           => '5002345678',
			'PossibleLength'          =>
				[
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
	'id'                            => 'AS',
	'countryCode'                   => 1,
	'internationalPrefix'           => '011',
	'nationalPrefix'                => '1',
	'nationalPrefixForParsing'      => '1|([267]\\d{6})$',
	'nationalPrefixTransformRule'   => '684$1',
	'sameMobileAndFixedLinePattern' => false,
	'numberFormat'                  =>
		[
		],
	'intlNumberFormat'              =>
		[
		],
	'mainCountryForCode'            => false,
	'leadingDigits'                 => '684',
	'leadingZeroPossible'           => false,
	'mobileNumberPortableRegion'    => false,
];