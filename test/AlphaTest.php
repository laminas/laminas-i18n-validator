<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator;

use Laminas\I18n\Validator\Alpha;
use Laminas\Validator\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function get_debug_type;
use function is_scalar;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class AlphaTest extends TestCase
{
    public function testThatLocaleIsARequiredOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The locale must be provided in the `locale` options key as a non-empty-string');

        new Alpha([]);
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
    public static function basicDataProvider(): array
    {
        $object            = (object) ['foo' => 'bar'];
        $stringableValid   = new StringableObject('abc');
        $stringableInvalid = new StringableObject('!!abc');

        return [
            // Invalid input types
            ['en', false, '', false, Alpha::STRING_EMPTY],
            ['en', false, [], false, Alpha::INVALID],
            ['en', false, false, false, Alpha::INVALID],
            ['en', false, true, false, Alpha::INVALID],
            ['en', false, 1.234, false, Alpha::INVALID],
            ['en', false, 1234, false, Alpha::INVALID],
            ['en', false, $object, false, Alpha::INVALID],
            // Whitespace Only
            ['en', false, "\n", false, Alpha::NOT_ALPHA],
            ['en', false, "\n\t", false, Alpha::NOT_ALPHA],
            ['en', true, "\n", true, null],
            ['en', true, "\n\t", true, null],
            // Unicode alphabets
            ['en', false, 'abc', true, null],
            ['en', false, 'a b c', false, Alpha::NOT_ALPHA],
            ['en', true, 'abc', true, null],
            ['en', true, 'a b c', true, null],
            ['en', false, 'abc123', false, Alpha::NOT_ALPHA],
            ['en', false, '123', false, Alpha::NOT_ALPHA],
            ['en', false, 'abc 123', false, Alpha::NOT_ALPHA],
            ['en', true, 'abc 123', false, Alpha::NOT_ALPHA],
            ['en', false, $stringableValid, true, null],
            ['en', false, $stringableInvalid, false, Alpha::NOT_ALPHA],
            ['en', false, 'grz5e4gżółka', false, Alpha::NOT_ALPHA],
            ['en', false, 'grzegżółka', true, null],
            ['en', false, 'Be3l5gië', false, Alpha::NOT_ALPHA],
            ['en', false, 'België', true, null],
            // Arabic Numbers
            ['ar@numbers=arab', false, "\xD9\xA1\xD9\xA1\xD9\xA1\xD9\xA1", false, Alpha::NOT_ALPHA],
            ['ar', false, "\xD9\xA1\xD9\xA1\xD9\xA1\xD9\xA1", false, Alpha::NOT_ALPHA],
            // In ja, ko and zh an ascii alphabet is used
            ['ja', false, '家', false, Alpha::NOT_ALPHA],
            ['ja', false, 'ッ', false, Alpha::NOT_ALPHA],
        ];
    }

    /** @param non-empty-string $locale */
    #[DataProvider('basicDataProvider')]
    public function testBasicBehaviour(
        string $locale,
        bool $allowWhiteSpace,
        mixed $input,
        bool $expectValid,
        string|null $expectMessageKey,
    ): void {
        $validator = new Alpha([
            'locale'          => $locale,
            'allowWhiteSpace' => $allowWhiteSpace,
        ]);

        self::assertSame(
            $expectValid,
            $validator->isValid($input),
            sprintf(
                'The input "%s" was expected to be %s but was not. %s',
                is_scalar($input) ? (string) $input : get_debug_type($input),
                $expectValid ? 'valid' : 'invalid',
                json_encode($validator->getMessages(), JSON_THROW_ON_ERROR),
            ),
        );

        if ($expectMessageKey === null) {
            return;
        }

        $messages = $validator->getMessages();
        self::assertArrayHasKey($expectMessageKey, $messages);
    }
}
