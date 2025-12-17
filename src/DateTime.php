<?php

declare(strict_types=1);

namespace Laminas\I18n\Validator;

use IntlDateFormatter;
use IntlException;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;

use function intl_is_failure;
use function is_string;

/**
 * Validate that the input is a date
 *
 * @psalm-type Options = array{
 *     locale: non-empty-string,
 *     timezone: non-empty-string,
 *     pattern?: string,
 *     dateType?: int,
 *     timeType?: int,
 *     calendar?: int,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class DateTime extends AbstractValidator
{
    public const INVALID          = 'datetimeInvalid';
    public const INVALID_DATETIME = 'datetimeInvalidDateTime';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID          => 'Invalid type given. String expected',
        self::INVALID_DATETIME => 'The input does not appear to be a valid datetime',
    ];

    /** @var non-empty-string */
    private readonly string $locale;
    private readonly int $calendar;
    private readonly int $dateType;
    private readonly int $timeType;
    /** @var non-empty-string */
    private readonly string $timezone;
    private readonly string $pattern;
    private IntlDateFormatter $formatter;

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

        $timezone = $options['timezone'] ?? null;
        /** @psalm-suppress DocblockTypeContradiction - Defensive check */
        if ($timezone === null || $timezone === '') {
            throw new InvalidArgumentException(
                'The desired timezone must be provided in the `timezone` options key as a non-empty-string',
            );
        }

        $this->locale   = $locale;
        $this->timezone = $timezone;
        $this->dateType = $options['dateType'] ?? IntlDateFormatter::NONE;
        $this->timeType = $options['timeType'] ?? IntlDateFormatter::NONE;
        $this->calendar = $options['calendar'] ?? IntlDateFormatter::GREGORIAN;
        $this->pattern  = $options['pattern'] ?? '';

        try {
            $this->formatter = $this->getIntlDateFormatter();

            if (intl_is_failure($this->formatter->getErrorCode())) {
                throw new InvalidArgumentException($this->formatter->getErrorMessage());
            }
        } catch (IntlException $intlException) {
            throw new InvalidArgumentException($intlException->getMessage(), 0, $intlException);
        }

        parent::__construct($options);
    }

    public function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
            $this->error(self::INVALID);

            return false;
        }

        $this->setValue($value);

        try {
            // Suppressing the warning because it is handled by `intl_is_failure`
            $timestamp = @$this->formatter->parse($value);

            if (intl_is_failure($this->formatter->getErrorCode()) || $timestamp === false) {
                $this->error(self::INVALID_DATETIME);
                return false;
            }
        } catch (IntlException) {
            $this->error(self::INVALID_DATETIME);
            return false;
        }

        return true;
    }

    /**
     * Returns a non-lenient configured IntlDateFormatter
     */
    private function getIntlDateFormatter(): IntlDateFormatter
    {
        $formatter = new IntlDateFormatter(
            $this->locale,
            $this->dateType,
            $this->timeType,
            $this->timezone,
            $this->calendar,
            $this->pattern,
        );

        $formatter->setLenient(false);

        return $formatter;
    }
}
