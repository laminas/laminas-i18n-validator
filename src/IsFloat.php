<?php

declare(strict_types=1);

namespace Laminas\I18n\Validator;

use IntlException;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception;
use Laminas\Validator\Exception\InvalidArgumentException;
use NumberFormatter;

use function assert;
use function intl_is_failure;
use function is_bool;
use function is_float;
use function is_int;
use function is_scalar;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_replace;

/**
 * Validates whether input represents a floating point number
 *
 * @psalm-type Options = array{
 *     locale?: non-empty-string,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class IsFloat extends AbstractValidator
{
    public const INVALID   = 'floatInvalid';
    public const NOT_FLOAT = 'notFloat';

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID   => 'Invalid type given. String, integer or float expected',
        self::NOT_FLOAT => 'The input does not appear to be a float',
    ];

    /** @var non-empty-string */
    private string $locale;

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

        parent::__construct($options);
    }

    public function isValid(mixed $value): bool
    {
        if (! is_scalar($value) || is_bool($value)) {
            $this->error(self::INVALID);
            return false;
        }

        if (is_float($value) || is_int($value)) {
            return true;
        }

        $this->setValue($value);

        if ($value === '') {
            $this->error(self::NOT_FLOAT);

            return false;
        }

        // Need to check if this is scientific formatted string. If not, switch to decimal.
        try {
            $formatter = new NumberFormatter($this->locale, NumberFormatter::SCIENTIFIC);
            if (intl_is_failure($formatter->getErrorCode())) {
                throw new Exception\InvalidArgumentException($formatter->getErrorMessage());
            }
        } catch (IntlException $intlException) {
            throw new Exception\InvalidArgumentException($intlException->getMessage(), 0, $intlException);
        }

        $exponentialSymbols = '[Ee' . $formatter->getSymbol(NumberFormatter::EXPONENTIAL_SYMBOL) . ']+';
        $search             = '/' . $exponentialSymbols . '/u';

        if (! preg_match($search, $value)) {
            $formatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);
        }

        /**
         * @desc There are separator "look-alikes" for decimal and group separators that are more commonly used than the
         *       official unicode character. We need to replace those with the real thing - or remove it.
         */
        $groupSeparator = $formatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        $decSeparator   = $formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        //NO-BREAK SPACE and ARABIC THOUSANDS SEPARATOR
        if ($groupSeparator === "\xC2\xA0") {
            $value = str_replace(' ', $groupSeparator, $value);
        } elseif ($groupSeparator === "\xD9\xAC") {
            // NumberFormatter doesn't have grouping at all for Arabic-Indic
            $value = str_replace(['\'', $groupSeparator], '', $value);
        }

        // ARABIC DECIMAL SEPARATOR
        if ($decSeparator === "\xD9\xAB") {
            $value = str_replace(',', $decSeparator, $value);
        }

        $groupSeparatorPosition = mb_strpos($value, $groupSeparator);
        $decSeparatorPosition   = mb_strpos($value, $decSeparator);

        //We have separators, and they are flipped. i.e. 2.000,000 for en-US
        if (
            $groupSeparatorPosition !== false
            && $decSeparatorPosition !== false
            && $groupSeparatorPosition > $decSeparatorPosition
        ) {
            $this->error(self::NOT_FLOAT);

            return false;
        }

        //If we have Unicode support, we can use the real graphemes, otherwise, just the ASCII characters
        $decimal = '[' . preg_quote($decSeparator, '/') . ']';
        $exp     = $exponentialSymbols;
        $prefix  = sprintf('[%s]{0,3}', preg_quote(
            $formatter->getTextAttribute(NumberFormatter::POSITIVE_PREFIX)
            . $formatter->getTextAttribute(NumberFormatter::NEGATIVE_PREFIX)
            . $formatter->getSymbol(NumberFormatter::PLUS_SIGN_SYMBOL)
            . $formatter->getSymbol(NumberFormatter::MINUS_SIGN_SYMBOL),
            '/',
        ));
        $suffix  = $formatter->getTextAttribute(NumberFormatter::NEGATIVE_SUFFIX);
        assert($suffix !== false);
        $suffix      = sprintf('[%s]{0,3}', preg_quote(
            $formatter->getTextAttribute(NumberFormatter::POSITIVE_SUFFIX)
            . $formatter->getTextAttribute(NumberFormatter::NEGATIVE_SUFFIX)
            . $formatter->getSymbol(NumberFormatter::PLUS_SIGN_SYMBOL)
            . $formatter->getSymbol(NumberFormatter::MINUS_SIGN_SYMBOL),
            '/'
        ));
        $numberRange = '\p{N}';

        /**
         * @see https://www.php.net/float
         *
         * @desc Match against the formal definition of a float. The
         *       exponential number check is modified for RTL non-Latin number
         *       systems (Arabic-Indic numbering). I'm also switching out the period
         *       for the decimal separator. The formal definition leaves out +- from
         *       the integer and decimal notations so add that.  This also checks
         *       that a grouping separator is not in the last GROUPING_SIZE graphemes
         *       of the string - i.e. 10,6 is not valid for en-US.
         */

        $lnum    = '[' . $numberRange . ']+';
        $dnum    = '(([' . $numberRange . ']*' . $decimal . $lnum . ')|('
            . $lnum . $decimal . '[' . $numberRange . ']*))';
        $expDnum = '((' . $prefix . '((' . $lnum . '|' . $dnum . ')' . $exp . $prefix . $lnum . ')' . $suffix . ')|'
            . '(' . $suffix . '(' . $lnum . $prefix . $exp . '(' . $dnum . '|' . $lnum . '))' . $prefix . '))';

        // LEFT-TO-RIGHT MARK (U+200E) is messing up everything for the handful
        // of locales that have it
        $lnumSearch     = str_replace("\xE2\x80\x8E", '', '/^' . $prefix . $lnum . $suffix . '$/u');
        $dnumSearch     = str_replace("\xE2\x80\x8E", '', '/^' . $prefix . $dnum . $suffix . '$/u');
        $expDnumSearch  = str_replace("\xE2\x80\x8E", '', '/^' . $expDnum . '$/u');
        $value          = str_replace("\xE2\x80\x8E", '', $value);
        $unGroupedValue = str_replace($groupSeparator, '', $value);

        // No strrpos() in wrappers yet. ICU 4.x doesn't have grouping size for
        // everything. ICU 52 has 3 for ALL locales.
        $groupSize = $formatter->getAttribute(NumberFormatter::GROUPING_SIZE);
        $groupSize = $groupSize === false ? 3 : $groupSize;
        assert(is_int($groupSize));
        $lastStringGroup = mb_strlen($value) > $groupSize
            ? mb_substr($value, 0 - $groupSize)
            : $value;

        assert($lastStringGroup !== '');
        assert($lnumSearch !== '');
        assert($dnumSearch !== '');
        assert($expDnumSearch !== '');

        if (
            (preg_match($lnumSearch, $unGroupedValue)
            || preg_match($dnumSearch, $unGroupedValue)
            || preg_match($expDnumSearch, $unGroupedValue))
            && false === mb_strpos($lastStringGroup, $groupSeparator)
        ) {
            return true;
        }

        $this->error(self::NOT_FLOAT);

        return false;
    }
}
