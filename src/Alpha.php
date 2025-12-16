<?php

declare(strict_types=1);

namespace Laminas\I18n\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;
use Locale;
use Stringable;

use function in_array;
use function is_string;
use function preg_replace;

/**
 * Validates whether input contains only alphabetical characters and optionally whitespace
 *
 * @psalm-type Options = array{
 *     allowWhiteSpace?: bool,
 *     locale?: non-empty-string,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class Alpha extends AbstractValidator
{
    public const INVALID      = 'alphaInvalid';
    public const NOT_ALPHA    = 'notAlpha';
    public const STRING_EMPTY = 'alphaStringEmpty';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID      => 'Invalid type given. String expected',
        self::NOT_ALPHA    => 'The input contains non-alphabetic characters',
        self::STRING_EMPTY => 'The input is an empty string',
    ];

    private readonly bool $allowWhiteSpace;
    /** @var non-empty-string */
    private readonly string $locale;

    /** @param Options $options */
    public function __construct(array $options = [])
    {
        $locale = $options['locale'] ?? null;
        /** @psalm-suppress DocblockTypeContradiction - Defensive check */
        if ($locale === null || $locale === '') {
            throw new InvalidArgumentException(
                'The locale must be provided in the `locale` options key as a non-empty-string',
            );
        }

        $this->locale          = $locale;
        $this->allowWhiteSpace = $options['allowWhiteSpace'] ?? false;

        parent::__construct($options);
    }

    public function isValid(mixed $value): bool
    {
        if (! is_string($value) && ! $value instanceof Stringable) {
            $this->error(self::INVALID);
            return false;
        }

        $value = (string) $value;

        $this->setValue($value);

        if ($value === '') {
            $this->error(self::STRING_EMPTY);
            return false;
        }

        $whiteSpace = $this->allowWhiteSpace ? '\s' : '';
        $language   = Locale::getPrimaryLanguage($this->locale);

        if (in_array($language, ['ja', 'ko', 'zh'], true)) {
            // Use english alphabet
            $pattern = '/[^a-zA-Z' . $whiteSpace . ']/u';
        } else {
            // Use native language alphabet
            $pattern = '/[^\p{L}' . $whiteSpace . ']/u';
        }

        $filtered = preg_replace($pattern, '', $value);
        if ($filtered !== $value) {
            $this->error(self::NOT_ALPHA);
            return false;
        }

        return true;
    }
}
