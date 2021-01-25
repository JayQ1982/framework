<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

declare(strict_types=1);

namespace framework\vendor\Respect\Validation;

use finfo;
use framework\vendor\Respect\Validation\Exceptions\ComponentException;
use framework\vendor\Respect\Validation\Exceptions\ValidationException;
use framework\vendor\Respect\Validation\Rules\AllOf;

use framework\vendor\Respect\Validation\Rules\Key;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;
use function count;

/**
 * @mixin StaticValidator
 * @author Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 * @author Henrique Moody <henriquemoody@gmail.com>
 */
final class Validator extends AllOf
{
	/**
	 * Create instance validator.
	 */
	public static function create(): self
	{
		return new self();
	}

	/**
	 * {@inheritDoc}
	 */
	public function check($input): void
	{
		try {
			parent::check($input);
		} catch (ValidationException $exception) {
			if (count($this->getRules()) == 1 && $this->template) {
				$exception->updateTemplate($this->template);
			}

			throw $exception;
		}
	}

	/**
	 * Creates a new Validator instance with a rule that was called on the static method.
	 *
	 * @param string  $ruleName
	 * @param mixed[] $arguments
	 *
	 * @return Validator
	 * @return Validator
	 * @return Validator
	 * @throws ComponentException
	 */
	public static function __callStatic(string $ruleName, array $arguments): self
	{
		return self::create()->__call($ruleName, $arguments);
	}

	/**
	 * Create a new rule by the name of the method and adds the rule to the chain.
	 *
	 * @param string  $ruleName
	 * @param mixed[] $arguments
	 *
	 * @return Validator
	 * @return Validator
	 * @return Validator
	 * @throws ComponentException
	 */
	public function __call(string $ruleName, array $arguments): self
	{
		$this->addRule(Factory::getDefaultInstance()->rule($ruleName, $arguments));

		return $this;
	}

	public static function allOf(Validatable ...$rule): ChainedValidator
	{
		// TODO: Implement allOf() method.
	}

	public static function alnum(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement alnum() method.
	}

	public static function alpha(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement alpha() method.
	}

	public static function alwaysInvalid(): ChainedValidator
	{
		// TODO: Implement alwaysInvalid() method.
	}

	public static function alwaysValid(): ChainedValidator
	{
		// TODO: Implement alwaysValid() method.
	}

	public static function anyOf(Validatable ...$rule): ChainedValidator
	{
		// TODO: Implement anyOf() method.
	}

	public static function arrayType(): ChainedValidator
	{
		// TODO: Implement arrayType() method.
	}

	public static function arrayVal(): ChainedValidator
	{
		// TODO: Implement arrayVal() method.
	}

	public static function attribute(string $reference, ?Validatable $validator = null, bool $mandatory = true): ChainedValidator
	{
		// TODO: Implement attribute() method.
	}

	public static function base(int $base, ?string $chars = null): ChainedValidator
	{
		// TODO: Implement base() method.
	}

	public static function base64(): ChainedValidator
	{
		// TODO: Implement base64() method.
	}

	public static function between($minimum, $maximum): ChainedValidator
	{
		// TODO: Implement between() method.
	}

	public static function bic(string $countryCode): ChainedValidator
	{
		// TODO: Implement bic() method.
	}

	public static function boolType(): ChainedValidator
	{
		// TODO: Implement boolType() method.
	}

	public static function boolVal(): ChainedValidator
	{
		// TODO: Implement boolVal() method.
	}

	public static function bsn(): ChainedValidator
	{
		// TODO: Implement bsn() method.
	}

	public static function call(callable $callable, Validatable $rule): ChainedValidator
	{
		// TODO: Implement call() method.
	}

	public static function callableType(): ChainedValidator
	{
		// TODO: Implement callableType() method.
	}

	public static function callback(callable $callback): ChainedValidator
	{
		// TODO: Implement callback() method.
	}

	public static function charset(string ...$charset): ChainedValidator
	{
		// TODO: Implement charset() method.
	}

	public static function cnh(): ChainedValidator
	{
		// TODO: Implement cnh() method.
	}

	public static function cnpj(): ChainedValidator
	{
		// TODO: Implement cnpj() method.
	}

	public static function control(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement control() method.
	}

	public static function consonant(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement consonant() method.
	}

	public static function contains($containsValue, bool $identical = false): ChainedValidator
	{
		// TODO: Implement contains() method.
	}

	public static function containsAny(array $needles, bool $strictCompareArray = false): ChainedValidator
	{
		// TODO: Implement containsAny() method.
	}

	public static function countable(): ChainedValidator
	{
		// TODO: Implement countable() method.
	}

	public static function countryCode(?string $set = null): ChainedValidator
	{
		// TODO: Implement countryCode() method.
	}

	public static function currencyCode(): ChainedValidator
	{
		// TODO: Implement currencyCode() method.
	}

	public static function cpf(): ChainedValidator
	{
		// TODO: Implement cpf() method.
	}

	public static function creditCard(?string $brand = null): ChainedValidator
	{
		// TODO: Implement creditCard() method.
	}

	public static function date(string $format = 'Y-m-d'): ChainedValidator
	{
		// TODO: Implement date() method.
	}

	public static function dateTime(?string $format = null): ChainedValidator
	{
		// TODO: Implement dateTime() method.
	}

	public static function decimal(int $decimals): ChainedValidator
	{
		// TODO: Implement decimal() method.
	}

	public static function digit(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement digit() method.
	}

	public static function directory(): ChainedValidator
	{
		// TODO: Implement directory() method.
	}

	public static function domain(bool $tldCheck = true): ChainedValidator
	{
		// TODO: Implement domain() method.
	}

	public static function each(Validatable $rule): ChainedValidator
	{
		// TODO: Implement each() method.
	}

	public static function email(): ChainedValidator
	{
		// TODO: Implement email() method.
	}

	public static function endsWith($endValue, bool $identical = false): ChainedValidator
	{
		// TODO: Implement endsWith() method.
	}

	public static function equals($compareTo): ChainedValidator
	{
		// TODO: Implement equals() method.
	}

	public static function equivalent($compareTo): ChainedValidator
	{
		// TODO: Implement equivalent() method.
	}

	public static function even(): ChainedValidator
	{
		// TODO: Implement even() method.
	}

	public static function executable(): ChainedValidator
	{
		// TODO: Implement executable() method.
	}

	public static function exists(): ChainedValidator
	{
		// TODO: Implement exists() method.
	}

	public static function extension(string $extension): ChainedValidator
	{
		// TODO: Implement extension() method.
	}

	public static function factor(int $dividend): ChainedValidator
	{
		// TODO: Implement factor() method.
	}

	public static function falseVal(): ChainedValidator
	{
		// TODO: Implement falseVal() method.
	}

	public static function fibonacci(): ChainedValidator
	{
		// TODO: Implement fibonacci() method.
	}

	public static function file(): ChainedValidator
	{
		// TODO: Implement file() method.
	}

	public static function filterVar(int $filter, $options = null): ChainedValidator
	{
		// TODO: Implement filterVar() method.
	}

	public static function finite(): ChainedValidator
	{
		// TODO: Implement finite() method.
	}

	public static function floatVal(): ChainedValidator
	{
		// TODO: Implement floatVal() method.
	}

	public static function floatType(): ChainedValidator
	{
		// TODO: Implement floatType() method.
	}

	public static function graph(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement graph() method.
	}

	public static function greaterThan($compareTo): ChainedValidator
	{
		// TODO: Implement greaterThan() method.
	}

	public static function hexRgbColor(): ChainedValidator
	{
		// TODO: Implement hexRgbColor() method.
	}

	public static function iban(): ChainedValidator
	{
		// TODO: Implement iban() method.
	}

	public static function identical($compareTo): ChainedValidator
	{
		// TODO: Implement identical() method.
	}

	public static function image(?finfo $fileInfo = null): ChainedValidator
	{
		// TODO: Implement image() method.
	}

	public static function imei(): ChainedValidator
	{
		// TODO: Implement imei() method.
	}

	public static function in($haystack, bool $compareIdentical = false): ChainedValidator
	{
		// TODO: Implement in() method.
	}

	public static function infinite(): ChainedValidator
	{
		// TODO: Implement infinite() method.
	}

	public static function instance(string $instanceName): ChainedValidator
	{
		// TODO: Implement instance() method.
	}

	public static function intVal(): ChainedValidator
	{
		// TODO: Implement intVal() method.
	}

	public static function intType(): ChainedValidator
	{
		// TODO: Implement intType() method.
	}

	public static function ip(string $range = '*', ?int $options = null): ChainedValidator
	{
		// TODO: Implement ip() method.
	}

	public static function isbn(): ChainedValidator
	{
		// TODO: Implement isbn() method.
	}

	public static function iterableType(): ChainedValidator
	{
		// TODO: Implement iterableType() method.
	}

	public static function json(): ChainedValidator
	{
		// TODO: Implement json() method.
	}

	public static function key(string $reference, ?Validatable $referenceValidator = null, bool $mandatory = true): ChainedValidator
	{
		// TODO: Implement key() method.
	}

	public static function keyNested(string $reference, ?Validatable $referenceValidator = null, bool $mandatory = true): ChainedValidator
	{
		// TODO: Implement keyNested() method.
	}

	public static function keySet(Key ...$rule): ChainedValidator
	{
		// TODO: Implement keySet() method.
	}

	public static function keyValue(string $comparedKey, string $ruleName, string $baseKey): ChainedValidator
	{
		// TODO: Implement keyValue() method.
	}

	public static function languageCode(?string $set = null): ChainedValidator
	{
		// TODO: Implement languageCode() method.
	}

	public static function leapDate(string $format): ChainedValidator
	{
		// TODO: Implement leapDate() method.
	}

	public static function leapYear(): ChainedValidator
	{
		// TODO: Implement leapYear() method.
	}

	public static function length(?int $min = null, ?int $max = null, bool $inclusive = true): ChainedValidator
	{
		// TODO: Implement length() method.
	}

	public static function lowercase(): ChainedValidator
	{
		// TODO: Implement lowercase() method.
	}

	public static function lessThan($compareTo): ChainedValidator
	{
		// TODO: Implement lessThan() method.
	}

	public static function luhn(): ChainedValidator
	{
		// TODO: Implement luhn() method.
	}

	public static function macAddress(): ChainedValidator
	{
		// TODO: Implement macAddress() method.
	}

	public static function max($compareTo): ChainedValidator
	{
		// TODO: Implement max() method.
	}

	public static function maxAge(int $age, ?string $format = null): ChainedValidator
	{
		// TODO: Implement maxAge() method.
	}

	public static function mimetype(string $mimetype): ChainedValidator
	{
		// TODO: Implement mimetype() method.
	}

	public static function min($compareTo): ChainedValidator
	{
		// TODO: Implement min() method.
	}

	public static function minAge(int $age, ?string $format = null): ChainedValidator
	{
		// TODO: Implement minAge() method.
	}

	public static function multiple(int $multipleOf): ChainedValidator
	{
		// TODO: Implement multiple() method.
	}

	public static function negative(): ChainedValidator
	{
		// TODO: Implement negative() method.
	}

	public static function nfeAccessKey(): ChainedValidator
	{
		// TODO: Implement nfeAccessKey() method.
	}

	public static function nif(): ChainedValidator
	{
		// TODO: Implement nif() method.
	}

	public static function nip(): ChainedValidator
	{
		// TODO: Implement nip() method.
	}

	public static function no(bool $useLocale = false): ChainedValidator
	{
		// TODO: Implement no() method.
	}

	public static function noneOf(Validatable ...$rule): ChainedValidator
	{
		// TODO: Implement noneOf() method.
	}

	public static function not(Validatable $rule): ChainedValidator
	{
		// TODO: Implement not() method.
	}

	public static function notBlank(): ChainedValidator
	{
		// TODO: Implement notBlank() method.
	}

	public static function notEmoji(): ChainedValidator
	{
		// TODO: Implement notEmoji() method.
	}

	public static function notEmpty(): ChainedValidator
	{
		// TODO: Implement notEmpty() method.
	}

	public static function notOptional(): ChainedValidator
	{
		// TODO: Implement notOptional() method.
	}

	public static function noWhitespace(): ChainedValidator
	{
		// TODO: Implement noWhitespace() method.
	}

	public static function nullable(Validatable $rule): ChainedValidator
	{
		// TODO: Implement nullable() method.
	}

	public static function nullType(): ChainedValidator
	{
		// TODO: Implement nullType() method.
	}

	public static function number(): ChainedValidator
	{
		// TODO: Implement number() method.
	}

	public static function numericVal(): ChainedValidator
	{
		// TODO: Implement numericVal() method.
	}

	public static function objectType(): ChainedValidator
	{
		// TODO: Implement objectType() method.
	}

	public static function odd(): ChainedValidator
	{
		// TODO: Implement odd() method.
	}

	public static function oneOf(Validatable ...$rule): ChainedValidator
	{
		// TODO: Implement oneOf() method.
	}

	public static function optional(Validatable $rule): ChainedValidator
	{
		// TODO: Implement optional() method.
	}

	public static function perfectSquare(): ChainedValidator
	{
		// TODO: Implement perfectSquare() method.
	}

	public static function pesel(): ChainedValidator
	{
		// TODO: Implement pesel() method.
	}

	public static function phone(): ChainedValidator
	{
		// TODO: Implement phone() method.
	}

	public static function phpLabel(): ChainedValidator
	{
		// TODO: Implement phpLabel() method.
	}

	public static function pis(): ChainedValidator
	{
		// TODO: Implement pis() method.
	}

	public static function polishIdCard(): ChainedValidator
	{
		// TODO: Implement polishIdCard() method.
	}

	public static function positive(): ChainedValidator
	{
		// TODO: Implement positive() method.
	}

	public static function postalCode(string $countryCode): ChainedValidator
	{
		// TODO: Implement postalCode() method.
	}

	public static function primeNumber(): ChainedValidator
	{
		// TODO: Implement primeNumber() method.
	}

	public static function printable(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement printable() method.
	}

	public static function punct(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement punct() method.
	}

	public static function readable(): ChainedValidator
	{
		// TODO: Implement readable() method.
	}

	public static function regex(string $regex): ChainedValidator
	{
		// TODO: Implement regex() method.
	}

	public static function resourceType(): ChainedValidator
	{
		// TODO: Implement resourceType() method.
	}

	public static function roman(): ChainedValidator
	{
		// TODO: Implement roman() method.
	}

	public static function scalarVal(): ChainedValidator
	{
		// TODO: Implement scalarVal() method.
	}

	public static function sf(Constraint $constraint, ?SymfonyValidator $validator = null): ChainedValidator
	{
		// TODO: Implement sf() method.
	}

	public static function size(?string $minSize = null, ?string $maxSize = null): ChainedValidator
	{
		// TODO: Implement size() method.
	}

	public static function slug(): ChainedValidator
	{
		// TODO: Implement slug() method.
	}

	public static function sorted(string $direction): ChainedValidator
	{
		// TODO: Implement sorted() method.
	}

	public static function space(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement space() method.
	}

	public static function startsWith($startValue, bool $identical = false): ChainedValidator
	{
		// TODO: Implement startsWith() method.
	}

	public static function stringType(): ChainedValidator
	{
		// TODO: Implement stringType() method.
	}

	public static function stringVal(): ChainedValidator
	{
		// TODO: Implement stringVal() method.
	}

	public static function subdivisionCode(string $countryCode): ChainedValidator
	{
		// TODO: Implement subdivisionCode() method.
	}

	public static function subset(array $superset): ChainedValidator
	{
		// TODO: Implement subset() method.
	}

	public static function symbolicLink(): ChainedValidator
	{
		// TODO: Implement symbolicLink() method.
	}

	public static function time(string $format = 'H:i:s'): ChainedValidator
	{
		// TODO: Implement time() method.
	}

	public static function tld(): ChainedValidator
	{
		// TODO: Implement tld() method.
	}

	public static function trueVal(): ChainedValidator
	{
		// TODO: Implement trueVal() method.
	}

	public static function type(string $type): ChainedValidator
	{
		// TODO: Implement type() method.
	}

	public static function unique(): ChainedValidator
	{
		// TODO: Implement unique() method.
	}

	public static function uploaded(): ChainedValidator
	{
		// TODO: Implement uploaded() method.
	}

	public static function uppercase(): ChainedValidator
	{
		// TODO: Implement uppercase() method.
	}

	public static function url(): ChainedValidator
	{
		// TODO: Implement url() method.
	}

	public static function uuid(?int $version = null): ChainedValidator
	{
		// TODO: Implement uuid() method.
	}

	public static function version(): ChainedValidator
	{
		// TODO: Implement version() method.
	}

	public static function videoUrl(?string $service = null): ChainedValidator
	{
		// TODO: Implement videoUrl() method.
	}

	public static function vowel(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement vowel() method.
	}

	public static function when(Validatable $if, Validatable $then, ?Validatable $else = null): ChainedValidator
	{
		// TODO: Implement when() method.
	}

	public static function writable(): ChainedValidator
	{
		// TODO: Implement writable() method.
	}

	public static function xdigit(string ...$additionalChars): ChainedValidator
	{
		// TODO: Implement xdigit() method.
	}

	public static function yes(bool $useLocale = false): ChainedValidator
	{
		// TODO: Implement yes() method.
	}

	public static function zend($validator, ?array $params = null): ChainedValidator
	{
		// TODO: Implement zend() method.
	}
}
