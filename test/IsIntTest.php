<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator;

use Laminas\I18n\Validator\IsInt;
use Laminas\Validator\Exception\InvalidArgumentException;
use NumberFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_map;
use function range;
use function sprintf;
use function str_repeat;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

final class IsIntTest extends TestCase
{
    public function testThatLocaleIsARequiredOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The locale must be provided in the `locale` options key as a non-empty-string');

        new IsInt([]);
    }

    /**
     * @return list<array{
     *     0: non-empty-string,
     *     1: bool,
     *     2: mixed,
     *     3: bool,
     *     4: string|null,
     * }>
     */
    public static function intDataProvider(): array
    {
        return [
            ['en', false, 1.00,         true,  null],
            ['en', false, 0.00,         true,  null],
            ['en', false, 0.01,         false, IsInt::NOT_INT],
            ['en', false, -0.1,         false, IsInt::NOT_INT],
            ['en', false, -1,           true,  null],
            ['en', false, '10',         true,  null],
            ['en', false, 1,            true,  null],
            ['en', false, 'not an int', false, IsInt::NOT_INT],
            ['en', false, true,         false, IsInt::INVALID],
            ['en', false, false,        false, IsInt::INVALID],
            ['en', false, PHP_INT_MAX,  true,  null],
            ['en', false, PHP_INT_MIN,  true,  null],
            ['en', true,  1.00,         false, IsInt::NOT_INT_STRICT],
            ['en', true,  0.00,         false, IsInt::NOT_INT_STRICT],
            ['en', true,  0.01,         false, IsInt::NOT_INT_STRICT],
            ['en', true,  -0.1,         false, IsInt::NOT_INT_STRICT],
            ['en', true,  -1,           true,  null],
            ['en', true,  '10',         false, IsInt::NOT_INT_STRICT],
            ['en', true,  1,            true,  null],
            ['en', true,  'not an int', false, IsInt::NOT_INT_STRICT],
            ['en', true,  true,         false, IsInt::INVALID],
            ['en', true,  false,        false, IsInt::INVALID],
            ['en', true,  PHP_INT_MAX,  true,  null],
            ['en', true,  PHP_INT_MIN,  true,  null],
        ];
    }

    /** @param non-empty-string $locale */
    #[DataProvider('intDataProvider')]
    public function testBasic(string $locale, bool $strict, mixed $value, bool $expected, string|null $errorKey): void
    {
        $validator = new IsInt(['locale' => $locale, 'strict' => $strict]);
        self::assertSame($expected, $validator->isValid($value));
        if ($errorKey === null) {
            return;
        }

        $messages = $validator->getMessages();
        self::assertArrayHasKey($errorKey, $messages);
    }

    public function testSettingLocales(): void
    {
        $validator = new IsInt(['locale' => 'de']);
        self::assertTrue($validator->isValid('10 000'));
        self::assertTrue($validator->isValid('10.000'));
        self::assertFalse($validator->isValid('10,99'));
    }

    public function testNonStringValidation(): void
    {
        $validator = new IsInt(['locale' => 'de']);
        self::assertFalse($validator->isValid([1 => 1]));
    }

    /**
     * @return iterable<string, array{
     *     0: non-empty-string,
     *     1: string,
     * }>
     */
    public static function numbersInDifferentLocalesProvider(): iterable
    {
        $locales = ['ar', 'bn', 'de', 'dz', 'en', 'fr-CH', 'ja', 'ks', 'ml-IN', 'mr', 'my', 'ps', 'ru', 'nl', 'ko'];
        $numbers = array_map(
            static fn (int $count): int => (int) str_repeat('1', $count),
            range(1, 12),
        );

        foreach ($locales as $locale) {
            $formatter = new NumberFormatter($locale, NumberFormatter::DEFAULT_STYLE);
            foreach ($numbers as $number) {
                $formatted = $formatter->format($number, NumberFormatter::TYPE_INT64);
                yield sprintf('%s (%s)', $formatted, $locale) => [
                    $locale,
                    $formatted,
                ];
            }
        }
    }

    /** @param non-empty-string $locale */
    #[DataProvider('numbersInDifferentLocalesProvider')]
    public function testFormattedIntegersInVariousLocales(string $locale, string $number): void
    {
        $validator = new IsInt([
            'locale' => $locale,
            'strict' => false,
        ]);

        self::assertTrue($validator->isValid($number));
    }

    public function testMessagesCanBeCustomised(): void
    {
        $validator = new IsInt([
            'locale'   => 'en',
            'messages' => [
                IsInt::NOT_INT => 'Bad News',
            ],
        ]);

        self::assertFalse($validator->isValid('!!'));
        self::assertSame([
            IsInt::NOT_INT => 'Bad News',
        ], $validator->getMessages());
    }
}
