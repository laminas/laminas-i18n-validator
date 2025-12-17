<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator;

use Laminas\I18n\Validator\Alnum;
use Laminas\Validator\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function get_debug_type;
use function is_scalar;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class AlnumTest extends TestCase
{
    public function testThatLocaleIsARequiredOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The locale must be provided in the `locale` options key as a non-empty-string');

        new Alnum([]);
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
        $stringableValid   = new StringableObject('abc123');
        $stringableInvalid = new StringableObject('!!abc');

        return [
            // Invalid input types
            ['en', false, '', false, Alnum::STRING_EMPTY],
            ['en', false, [], false, Alnum::INVALID],
            ['en', false, false, false, Alnum::INVALID],
            ['en', false, $object, false, Alnum::INVALID],
            ['en', false, 1.234, false, Alnum::NOT_ALNUM],
            // Whitespace Only
            ['en', false, "\n", false, Alnum::NOT_ALNUM],
            ['en', false, "\n\t", false, Alnum::NOT_ALNUM],
            ['en', true, "\n", true, null],
            ['en', true, "\n\t", true, null],
            // Unicode alphabets
            ['en', false, 'abc', true, null],
            ['en', false, 'abc123', true, null],
            ['en', false, '123', true, null],
            ['en', false, 123, true, null],
            ['en', false, 'abc 123', false, Alnum::NOT_ALNUM],
            ['en', true, 'abc 123', true, null],
            ['en', false, $stringableValid, true, null],
            ['en', false, $stringableInvalid, false, Alnum::NOT_ALNUM],
            ['en', false, 'grz5e4gżółka', true, null],
            ['en', false, 'Be3l5gië', true, null],
            // Arabic Numbers
            ['ar@numbers=arab', false, "\xD9\xA1\xD9\xA1\xD9\xA1\xD9\xA1", true, null],
            ['ar', false, "\xD9\xA1\xD9\xA1\xD9\xA1\xD9\xA1", true, null],
            // In ja, ko and zh an ascii alphabet is used
            ['ja', false, '家', false, Alnum::NOT_ALNUM],
            ['ja', false, 'ッ', false, Alnum::NOT_ALNUM],
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
        $validator = new Alnum([
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

    public function testMessagesCanBeCustomised(): void
    {
        $validator = new Alnum([
            'locale'   => 'en',
            'messages' => [
                Alnum::NOT_ALNUM => 'Bad News',
            ],
        ]);

        self::assertFalse($validator->isValid('!!'));
        self::assertSame([
            Alnum::NOT_ALNUM => 'Bad News',
        ], $validator->getMessages());
    }
}
