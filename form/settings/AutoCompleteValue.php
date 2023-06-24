<?php
/**
 * @author    Christof Moser <framework@actra.ch>
 * @copyright Actra AG, Rümlang, Switzerland
 */

namespace framework\form\settings;

// See https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete
enum AutoCompleteValue: string
{
	case OFF = 'off';
	case ON = 'on';
	case NAME = 'name';
	case HONORIFIC_PREFIX = 'honorific-prefix';
	case GIVEN_NAME = 'given-name';
	case ADDITIONAL_NAME = 'additional-name';
	case FAMILY_NAME = 'family-name';
	case HONORIFIC_SUFFIX = 'honorific-suffix';
	case NICKNAME = 'nickname';
	case EMAIL = 'email';
	case USERNAME = 'username';
	case NEW_PASSWORD = 'new-password';
	case CURRENT_PASSWORD = 'current-password';
	case ONE_TIME_CODE = 'one-time-code';
	case ORGANIZATION_TITLE = 'organization-title';
	case ORGANIZATION = 'organization';
	case STREET_ADDRESS = 'street-address';
	case ADDRESS_LINE_1 = 'address-line1';
	case ADDRESS_LINE_2 = 'address-line2';
	case ADDRESS_LINE_3 = 'address-line3';
	case ADDRESS_LEVEL4 = 'address-level4';
	case ADDRESS_LEVEL3 = 'address-level3';
	case ADDRESS_LEVEL2 = 'address-level2';
	case ADDRESS_LEVEL1 = 'address-level1';
	case COUNTRY = 'country';
	case COUNTRY_NAME = 'country-name';
	case POSTAL_CODE = 'postal-code';
	case CC_NAME = 'cc-name';
	case CC_GIVEN_NAME = 'cc-given-name';
	case CC_ADDITIONAL_NAME = 'cc-additional-name';
	case CC_FAMILY_NAME = 'cc-family-name';
	case CC_NUMBER = 'cc-number';
	case CC_EXP = 'cc-exp';
	case CC_EXP_MONTH = 'cc-exp-month';
	case CC_EXP_YEAR = 'cc-exp-year';
	case CC_CSC = 'cc-csc';
	case CC_TYPE = 'cc-type';
	case TRANSACTION_CURRENCY = 'transaction-currency';
	case TRANSACTION_AMOUNT = 'transaction-amount';
	case LANGUAGE = 'language';
	case BDAY = 'bday';
	case BDAY_DAY = 'bday-day';
	case BDAY_MONTH = 'bday-month';
	case BDAY_YEAR = 'bday-year';
	case SEX = 'sex';
	case TEL = 'tel';
	case TEL_COUNTRY_CODE = 'tel-country-code';
	case TEL_NATIONAL = 'tel-national';
	case TEL_AREA_CODE = 'tel-area-code';
	case TEL_LOCAL = 'tel-local';
	case TEL_EXTENSION = 'tel-extension';
	case IMPP = 'impp';
	case URL = 'url';
	case PHOTO = 'photo';

}