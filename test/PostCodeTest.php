<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator;

use Generator;
use Laminas\I18n\Validator\PostCode;
use Laminas\Validator\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function preg_last_error;
use function preg_match;
use function sprintf;

use const PREG_NO_ERROR;

final class PostCodeTest extends TestCase
{
    /** @return array<array-key, array{0: string, 1: bool}> */
    public static function gbPostCodesDataProvider(): array
    {
        return [
            ['CA3 5JQ', true],
            ['GL15 2GB', true],
            ['GL152GB', true],
            ['ECA32 6JQ', false],
            ['se5 0eg', false],
            ['SE5 0EG', true],
            ['ECA3 5JQ', false],
            ['WC2H 7LTa', false],
            ['WC2H 7LTA', false],
        ];
    }

    #[DataProvider('gbPostCodesDataProvider')]
    public function testUKBasic(string $postCode, bool $expected): void
    {
        $ukValidator = new PostCode(['locale' => 'en_GB']);
        self::assertSame($expected, $ukValidator->isValid($postCode));
    }

    /** @return array<array-key, array{0: mixed, 1: bool}> */
    public static function dePostCodesDataProvider(): array
    {
        return [
            ['2292',    true],
            ['1000',    true],
            ['0000',    true],
            ['12345',   false],
            [1234,      true],
            [9821,      true],
            ['21A4',    false],
            ['ABCD',    false],
            [true,      false],
            ['AT-2292', false],
            [1.56,      false],
        ];
    }

    #[DataProvider('dePostCodesDataProvider')]
    public function testBasic(mixed $postCode, bool $expected): void
    {
        $validator = new PostCode(['locale' => 'de_AT']);
        self::assertSame($expected, $validator->isValid($postCode));
    }

    public function testOmittingTheLocaleAndACustomPatternCausesAnException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('One of `format` or `locale` must be provided');
        new PostCode([]);
    }

    public function testThatGivenLocalesMustHaveKnownRegionsWhenFormatIsNotGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The locale "jj_XC" does not represent a known geographic region');
        new PostCode(['locale' => 'jj_XC']);
    }

    public function testThatGivenLocalesAreIgnoredWhenFormatIsGiven(): void
    {
        $validator = new PostCode(['locale' => 'jj_XC', 'format' => '/^\d{3}z$/']);
        self::assertTrue($validator->isValid('111z'));
    }

    public function testCustomFormatMustBeNonEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom post code format patterns must be non-empty-string');

        /** @psalm-suppress InvalidArgument */
        new PostCode(['format' => '']);
    }

    /**
     * @return list<array{
     *     0: non-empty-string,
     *     1: mixed,
     *     2: bool,
     * }>
     */
    public static function customFormatProvider(): array
    {
        return [
            ['/^[0-5]{3}$/', '333', true],
            ['/^[0-5]{3}$/', '789', false],
            ['/^[0-5]{3}$/', 123, true],
            ['/^[0-5]{3}$/', 789, false],
        ];
    }

    /** @param non-empty-string $format */
    #[DataProvider('customFormatProvider')]
    public function testCustomFormat(string $format, mixed $input, bool $expect): void
    {
        $validator = new PostCode(['format' => $format]);

        self::assertSame($expect, $validator->isValid($input));
    }

    public function testMalformedCustomRegex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The format pattern "({()" is not a valid regex');
        $validator = new PostCode(['format' => '({()']);

        $validator->isValid('foo');
    }

    public function testErrorMessageText(): void
    {
        $validator = new PostCode(['locale' => 'de_AT']);
        self::assertFalse($validator->isValid('hello'));
        $message = $validator->getMessages();
        self::assertArrayHasKey(PostCode::NO_MATCH, $message);
        self::assertStringContainsString(
            'not appear to be a postal code',
            $message[PostCode::NO_MATCH],
        );
    }

    /**
     * Post codes are provided by French government official post code database
     * https://www.data.gouv.fr/fr/datasets/base-officielle-des-codes-postaux/
     */
    public function testFrPostCodes(): void
    {
        $validator = new PostCode(['locale' => 'fr_FR']);

        self::assertTrue($validator->isValid('13100')); // AIX EN PROVENCE
        self::assertTrue($validator->isValid('97439')); // STE ROSE
        self::assertTrue($validator->isValid('98790')); // MAHETIKA
        self::assertFalse($validator->isValid('00000')); // Post codes starting with 00 don't exist
        self::assertFalse($validator->isValid('96000')); // Post codes starting with 96 don't exist
        self::assertFalse($validator->isValid('99000')); // Post codes starting with 99 don't exist
    }

    /**
     * Post codes are provided by Norway Mail database
     * http://www.bring.no/hele-bring/produkter-og-tjenester/brev-og-postreklame/andre-tjenester/postnummertabeller
     */
    public function testNoPostCodes(): void
    {
        $validator = new PostCode(['locale' => 'en_NO']);

        self::assertTrue($validator->isValid('0301')); // OSLO
        self::assertTrue($validator->isValid('9910')); // BJØRNEVATN
        self::assertFalse($validator->isValid('0000')); // Postal code 0000
    }

    /**
     * Postal codes in Latvia are 4 digit numeric and use a mandatory ISO 3166-1 alpha-2 country code (LV) in front,
     * i.e. the format is “LV-NNNN”.
     * To prevent BC break LV- prefix is optional
     * https://en.wikipedia.org/wiki/Postal_codes_in_Latvia
     */
    public function testLvPostCodes(): void
    {
        $validator = new PostCode(['locale' => 'en_LV']);

        self::assertTrue($validator->isValid('LV-0000'));
        self::assertTrue($validator->isValid('0000'));
        self::assertFalse($validator->isValid('ABCD'));
        self::assertFalse($validator->isValid('LV-ABCD'));
    }

    /** @return Generator<string, array{0: int}> */
    public static function liPostCode(): Generator
    {
        yield 'Nendeln' => [9485];
        yield 'Schaanwald' => [9486];
        yield 'Gamprin-Bendern' => [9487];
        yield 'Schellenberg' => [9488];
        yield 'Vaduz-9489' => [9489];
        yield 'Vaduz-9490' => [9490];
        yield 'Ruggell' => [9491];
        yield 'Eschen' => [9492];
        yield 'Mauren' => [9493];
        yield 'Schaan' => [9494];
        yield 'Triesen' => [9495];
        yield 'Balzers' => [9496];
        yield 'Triesenberg' => [9497];
        yield 'Planken' => [9498];
    }

    #[DataProvider('liPostCode')]
    public function testLiPostCodes(int $postCode): void
    {
        $validator = new PostCode(['locale' => 'de_LI']);

        self::assertTrue($validator->isValid($postCode));
    }

    public function testInternalRegexesAreValidPatterns(): void
    {
        $class    = new ReflectionClass(PostCode::class);
        $constant = $class->getReflectionConstant('POST_CODE_REGEX');
        self::assertNotFalse($constant);
        $list = $constant->getValue();
        self::assertIsArray($list);

        foreach ($list as $item) {
            self::assertIsString($item);

            /** @psalm-suppress UnusedFunctionCall */
            @preg_match(sprintf('/^%s$/', $item), 'whatever');

            self::assertSame(PREG_NO_ERROR, preg_last_error());
        }
    }

    public function testMessagesCanBeCustomised(): void
    {
        $validator = new PostCode([
            'locale'   => 'en_GB',
            'messages' => [
                PostCode::NO_MATCH => 'Bad News',
            ],
        ]);

        self::assertFalse($validator->isValid('!!'));
        self::assertSame([
            PostCode::NO_MATCH => 'Bad News',
        ], $validator->getMessages());
    }
}
