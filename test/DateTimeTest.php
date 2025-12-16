<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator;

use DateTime;
use IntlDateFormatter;
use Laminas\I18n\Validator\DateTime as DateTimeValidator;
use Laminas\Validator\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function get_debug_type;
use function is_scalar;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;
use const PHP_INT_MAX;

/** @psalm-import-type Options from DateTimeValidator */
final class DateTimeTest extends TestCase
{
    /**
     * Ensures that the validator follows expected behaviour
     *
     * @param Options $options
     */
    #[DataProvider('basicProvider')]
    public function testBasic(mixed $value, bool $expected, array $options): void
    {
        $validator = new DateTimeValidator($options);

        self::assertSame(
            $expected,
            $validator->isValid($value),
            sprintf(
                'The value "%s" was expected to be %s, but it was not. Options: %s',
                is_scalar($value) ? (string) $value : get_debug_type($value),
                $expected ? 'valid' : 'invalid',
                json_encode($options, JSON_THROW_ON_ERROR),
            ),
        );
    }

    /**
     * @return list<array{
     *     0: string,
     *     1: boolean,
     *     2: Options,
     * }>
     */
    public static function basicProvider(): array
    {
        $trueArray      = [];
        $testingDate    = new DateTime();
        $testingLocales = ['en', 'de', 'zh-TW', 'ja', 'ar', 'ru', 'si', 'ml-IN', 'hi'];
        $testingFormats = [
            IntlDateFormatter::FULL,
            IntlDateFormatter::LONG,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE,
        ];

        //Loop locales and formats for a more thorough set of "true" test data
        foreach ($testingLocales as $locale) {
            foreach ($testingFormats as $dateFormat) {
                foreach ($testingFormats as $timeFormat) {
                    if (($timeFormat !== IntlDateFormatter::NONE) || ($dateFormat !== IntlDateFormatter::NONE)) {
                        $formatter = IntlDateFormatter::create($locale, $dateFormat, $timeFormat);
                        self::assertNotNull($formatter);
                        $trueArray[] = [
                            $formatter->format($testingDate),
                            true,
                            [
                                'locale'   => $locale,
                                'timezone' => 'Europe/Amsterdam',
                                'dateType' => $dateFormat,
                                'timeType' => $timeFormat,
                            ],
                        ];
                    }
                }
            }
        }

        $falseArray = [
            [
                'May 38, 2013',
                false,
                [
                    'locale'   => 'en',
                    'timezone' => 'Europe/Amsterdam',
                    'dateType' => IntlDateFormatter::FULL,
                    'timeType' => IntlDateFormatter::NONE,
                ],
            ],
        ];

        return [
            ...$trueArray,
            ...$falseArray,
        ];
    }

    /**
     * Ensures that an omitted pattern results in a calculated pattern by IntlDateFormatter
     */
    public function testLocaleDependentPatternIsUsedWhenPatternIsOmitted(): void
    {
        $validator = new DateTimeValidator([
            'locale'   => 'en_GB',
            'timezone' => 'Europe/London',
            'dateType' => IntlDateFormatter::SHORT,
        ]);

        self::assertTrue($validator->isValid('1/1/2020 10:30'));
        self::assertFalse($validator->isValid('2020-01-01 10:30'));
    }

    public function testSettingThePatternToNullIsAcceptable(): void
    {
        /** @psalm-suppress InvalidArgument the null pattern is invalid but not actually problematic */
        $validator = new DateTimeValidator([
            'locale'   => 'en_GB',
            'timezone' => 'Europe/London',
            'dateType' => IntlDateFormatter::SHORT,
            'timeType' => IntlDateFormatter::SHORT,
            'pattern'  => null,
        ]);

        self::assertTrue($validator->isValid('1/1/2020, 10:34'));
    }

    public function testSettingThePatternToAnEmptyStringIsAcceptable(): void
    {
        $validator = new DateTimeValidator([
            'locale'   => 'en_GB',
            'timezone' => 'Europe/London',
            'dateType' => IntlDateFormatter::SHORT,
            'timeType' => IntlDateFormatter::SHORT,
            'pattern'  => '',
        ]);

        self::assertTrue($validator->isValid('1/1/2020, 10:34'));
    }

    public function testMultipleIsValidCalls(): void
    {
        $formatter = IntlDateFormatter::create('en', IntlDateFormatter::FULL, IntlDateFormatter::FULL);
        self::assertNotNull($formatter);
        $validValue = $formatter->format(new DateTime());
        $validator  = new DateTimeValidator([
            'locale'   => 'en',
            'timezone' => 'Europe/London',
            'dateType' => IntlDateFormatter::FULL,
            'timeType' => IntlDateFormatter::FULL,
        ]);

        self::assertTrue($validator->isValid($validValue));
        self::assertFalse($validator->isValid('12/31/2015'));
        self::assertFalse($validator->isValid('23:59:59'));
        self::assertFalse($validator->isValid('does not matter'));
        self::assertTrue($validator->isValid($validValue));
    }

    public function testThatLocaleIsARequiredOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The locale must be provided in the `locale` options key as a non-empty-string');

        /** @psalm-suppress InvalidArgument */
        new DateTimeValidator([]);
    }

    public function testThatTimezoneIsARequiredOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The desired timezone must be provided in the `timezone` options key as a non-empty-string',
        );

        /** @psalm-suppress InvalidArgument */
        new DateTimeValidator(['locale' => 'en']);
    }

    public function testThatInvalidFormatterOptionsCauseExceptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid date format style');

        new DateTimeValidator([
            'locale'   => 'en',
            'timezone' => 'Europe/London',
            'dateType' => PHP_INT_MAX,
        ]);
    }

    /** @return list<array{0: mixed}> */
    public static function invalidValues(): array
    {
        return [
            [(object) ['foo' => 'bar']],
            [['baz']],
            [1],
            [1.5],
            [true],
            [null],
            [false],
        ];
    }

    #[DataProvider('invalidValues')]
    public function testInvalidInput(mixed $input): void
    {
        $validator = new DateTimeValidator([
            'locale'   => 'en',
            'timezone' => 'Europe/London',
        ]);

        self::assertFalse($validator->isValid($input));
        $messages = $validator->getMessages();
        self::assertArrayHasKey(DateTimeValidator::INVALID, $messages);
    }

    public function testMessagesCanBeCustomised(): void
    {
        $validator = new DateTimeValidator([
            'locale'   => 'en',
            'timezone' => 'UTC',
            'messages' => [
                DateTimeValidator::INVALID_DATETIME => 'Bad News',
            ],
        ]);

        self::assertFalse($validator->isValid('!!'));
        self::assertSame([
            DateTimeValidator::INVALID_DATETIME => 'Bad News',
        ], $validator->getMessages());
    }
}
