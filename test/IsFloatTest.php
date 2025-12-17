<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator;

use Laminas\I18n\Validator\IsFloat;
use Laminas\Validator\Exception\InvalidArgumentException;
use NumberFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function get_debug_type;
use function is_scalar;
use function sprintf;

use const INTL_ICU_DATA_VERSION;
use const INTL_ICU_VERSION;

final class IsFloatTest extends TestCase
{
    private IsFloat $validator;

    protected function setUp(): void
    {
        $this->validator = new IsFloat(['locale' => 'en']);
    }

    public function testThatLocaleIsARequiredOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The locale must be provided in the `locale` options key as a non-empty-string');

        new IsFloat([]);
    }

    /**
     * Test float and integer type variables. Includes decimal and scientific notation NumberFormatter-formatted
     * versions. Should return true for all locales.
     *
     * @param non-empty-string $locale
     */
    #[DataProvider('floatAndIntegerProvider')]
    public function testFloatAndIntegers(mixed $value, bool $expected, string $locale, string $type): void
    {
        $validator = new IsFloat(['locale' => $locale]);

        self::assertSame(
            $expected,
            $validator->isValid($value),
            sprintf(
                'Failed expecting %s being %s (locale: %s, type: %s, ICU Version: %s - %s)',
                is_scalar($value) ? (string) $value : get_debug_type($value),
                $expected ? 'valid' : 'invalid',
                $locale,
                $type,
                INTL_ICU_VERSION,
                INTL_ICU_DATA_VERSION,
            ),
        );
    }

    /**
     * @return list<array{
     *    0: mixed,
     *    1: bool,
     *    2: non-empty-string,
     *    3: string,
     * }>
     */
    public static function floatAndIntegerProvider(): array
    {
        $trueArray       = [];
        $testingLocales  = ['ar', 'bn', 'de', 'dz', 'en', 'fr-CH', 'ja', 'ks', 'ml-IN', 'mr', 'my', 'ps', 'ru'];
        $testingExamples = [
            1000,
            -2000,
            +398.00,
            0.04,
            -0.5,
            .6,
            -.70,
            8E10,
            -9.3456E-2,
            10.23E6,
            123.1234567890987654321,
            1,
            13,
            -3,
        ];

        // Loop locales and examples for a more thorough set of "true" test data
        foreach ($testingLocales as $locale) {
            foreach ($testingExamples as $example) {
                // The value as a regular float or int
                $trueArray[] = [$example, true, $locale, 'raw'];

                // Decimal Formatted String
                $numberFormatter = NumberFormatter::create($locale, NumberFormatter::DECIMAL);
                self::assertInstanceOf(NumberFormatter::class, $numberFormatter);
                $trueArray[] = [
                    $numberFormatter->format($example, NumberFormatter::TYPE_DOUBLE),
                    true,
                    $locale,
                    'decimal',
                ];

                // Scientific Notation Formatted String
                $numberFormatter = NumberFormatter::create($locale, NumberFormatter::SCIENTIFIC);
                self::assertInstanceOf(NumberFormatter::class, $numberFormatter);
                $trueArray[] = [
                    $numberFormatter->format($example, NumberFormatter::TYPE_DOUBLE),
                    true,
                    $locale,
                    'scientific',
                ];
            }
        }

        return $trueArray;
    }

    /**
     * Test manually-generated strings for specific locales. These are "look-alike" strings where graphemes such as
     * NO-BREAK SPACE, ARABIC THOUSANDS SEPARATOR, and ARABIC DECIMAL SEPARATOR are replaced with more typical ASCII
     * characters.
     *
     * @param string  $value that will be tested
     * @param boolean $expected expected result of assertion
     * @param non-empty-string $locale locale for validation
     */
    #[DataProvider('lookAlikeProvider')]
    public function testLookALikes(string $value, bool $expected, string $locale): void
    {
        $validator = new IsFloat([
            'locale' => $locale,
        ]);

        self::assertSame(
            $expected,
            $validator->isValid($value),
            sprintf(
                'The value ' . "\n" . '%s' . "\n" . ' was expected to be %s for the locale "%s"',
                $value,
                $expected ? 'valid' : 'invalid',
                $locale,
            ),
        );
    }

    /** @return array<array-key, array{0: string, 1: bool, 2: non-empty-string}> */
    public static function lookAlikeProvider(): array
    {
        $trueArray    = [];
        $testingArray = [
            /**
             * 1,111.23 in Arabic
             *
             * This case fails on MacOS and in the shipped Docker image here without `@numbers=arab`
             * https://stackoverflow.com/questions/78021672/missing-arabic-numbers-when-formatting-date-using-intldateformatter-on-php-8-2-a
             */
            'ar@numbers=arab' => "\xD9\xA1'\xD9\xA1\xD9\xA1\xD9\xA1,\xD9\xA2\xD9\xA3",
            'ru'              => '2 000,00',
        ];

        // Loop locales and examples for a more thorough set of "true" test data
        foreach ($testingArray as $locale => $example) {
            $trueArray[] = [$example, true, $locale];
        }

        return $trueArray;
    }

    /**
     * @param string $value that will be tested
     * @param boolean $expected expected result of assertion
     * @param non-empty-string $locale locale for validation
     */
    #[DataProvider('validationFailureProvider')]
    public function testValidationFailures(string $value, bool $expected, string $locale): void
    {
        $validator = new IsFloat([
            'locale' => $locale,
        ]);

        self::assertSame(
            $expected,
            $validator->isValid($value),
            'Failed expecting ' . $value . ' being ' . ($expected ? 'true' : 'false') . sprintf(' (locale:%s)', $locale)
        );
    }

    /** @return list<array{0: string, 1: bool, 2: non-empty-string}> */
    public static function validationFailureProvider(): array
    {
        $trueArray    = [];
        $testingArray = [
            // Ensure arabic numerals are expected in order for this test to fail
            'ar@numbers=arab' => ['10.1', '66notflot.6'],
            'ru'              => ['10.1', '66notflot.6', '2,000.00', '2 00'],
            'en'              => ['10,1', '66notflot.6', '2.000,00', '2 000', '2,00'],
            'fr-CH'           => ['66notflot.6', '2,000.00', "2'00"],
        ];

        //Loop locales and examples for a more thorough set of "true" test data
        foreach ($testingArray as $locale => $exampleArray) {
            foreach ($exampleArray as $example) {
                $trueArray[] = [$example, false, $locale];
            }
        }
        return $trueArray;
    }

    public function testNonStringValidation(): void
    {
        self::assertFalse($this->validator->isValid([1 => 1]));
    }

    public function testNotFloat(): void
    {
        self::assertFalse($this->validator->isValid('2.000.000,00'));

        $message = $this->validator->getMessages();
        self::assertStringContainsString('does not appear to be a float', $message['notFloat']);
    }

    public function testEmptyStringShouldReturnStandardErrorMessage(): void
    {
        self::assertFalse($this->validator->isValid(''));
        $message = $this->validator->getMessages();
        self::assertStringContainsString(
            'does not appear to be a float',
            $message['notFloat']
        );
    }

    public function testMessagesCanBeCustomised(): void
    {
        $validator = new IsFloat([
            'locale'   => 'en',
            'messages' => [
                IsFloat::NOT_FLOAT => 'Bad News',
            ],
        ]);

        self::assertFalse($validator->isValid('!!'));
        self::assertSame([
            IsFloat::NOT_FLOAT => 'Bad News',
        ], $validator->getMessages());
    }
}
