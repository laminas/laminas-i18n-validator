<?php

declare(strict_types=1);

namespace Laminas\I18n\Validator;

use IntlException;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception;
use Laminas\Validator\Exception\InvalidArgumentException;
use NumberFormatter;

use function fmod;
use function intl_is_failure;
use function is_float;
use function is_int;
use function is_string;

/**
 * Validate whether the input is valid integer value
 *
 * @psalm-type Options = array{
 *     strict?: bool,
 *     locale?: non-empty-string,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class IsInt extends AbstractValidator
{
    public const INVALID        = 'intInvalid';
    public const NOT_INT        = 'notInt';
    public const NOT_INT_STRICT = 'notIntStrict';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID        => 'Invalid type given. String or integer expected',
        self::NOT_INT        => 'The input does not appear to be an integer',
        self::NOT_INT_STRICT => 'The input is not strictly an integer',
    ];

    private readonly string $locale;

    /**
     * Data type is not enforced by default, so the string '123' is considered an integer.
     * Setting strict to true will enforce the integer data type.
     */
    private readonly bool $strict;

    /** @param Options $options */
    public function __construct(array $options)
    {
        $locale = $options['locale'] ?? null;
        /** @psalm-suppress DocblockTypeContradiction - Defensive check */
        if ($locale === null || $locale === '') {
            throw new InvalidArgumentException(
                'The locale must be provided in the `locale` options key as a non-empty-string',
            );
        }

        $this->locale = $locale;
        $this->strict = $options['strict'] ?? false;

        parent::__construct($options);
    }

    /**
     * Returns true if and only if $value is a valid integer
     *
     * @throws Exception\InvalidArgumentException
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            $this->error(self::INVALID);
            return false;
        }

        if (is_int($value)) {
            return true;
        }

        if ($this->strict) {
            $this->error(self::NOT_INT_STRICT);
            return false;
        }

        $this->setValue($value);

        $value = (string) $value;

        try {
            $format = new NumberFormatter($this->locale, NumberFormatter::DEFAULT_STYLE);
            if (intl_is_failure($format->getErrorCode())) {
                throw new Exception\InvalidArgumentException('Invalid locale string given');
            }
        } catch (IntlException $intlException) {
            throw new Exception\InvalidArgumentException('Invalid locale string given', 0, $intlException);
        }

        try {
            $parsedInt = $format->parse($value);
            if (intl_is_failure($format->getErrorCode())) {
                $this->error(self::NOT_INT);
                return false;
            }
        } catch (IntlException) {
            $this->error(self::NOT_INT);
            return false;
        }

        if (fmod($parsedInt, 1.0) !== 0.0) {
            $this->error(self::NOT_INT);

            return false;
        }

        return true;
    }
}
