<?php

declare(strict_types=1);

namespace Laminas\I18n\Validator;

use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;
use Locale;

use function array_key_exists;
use function assert;
use function is_int;
use function is_string;
use function preg_last_error;
use function preg_last_error_msg;
use function preg_match;
use function sprintf;
use function str_ends_with;
use function str_starts_with;

use const PREG_NO_ERROR;

/**
 * Validate whether the input is valid postal code
 *
 * @psalm-type Options = array{
 *     locale?: non-empty-string,
 *     format?: non-empty-string,
 *     messages?: array<string, string>,
 *     translator?: TranslatorInterface|null,
 *     translatorTextDomain?: string|null,
 *     translatorEnabled?: bool,
 *     valueObscured?: bool,
 * }
 */
final class PostCode extends AbstractValidator
{
    public const INVALID  = 'postcodeInvalid';
    public const NO_MATCH = 'postcodeNoMatch';

    private const POST_CODE_REGEX = [
        // phpcs:ignore Generic.Files.LineLength
        'GB' => 'GIR[ ]?0AA|^((AB|AL|B|BA|BB|BD|BH|BL|BN|BR|BS|BT|CA|CB|CF|CH|CM|CO|CR|CT|CV|CW|DA|DD|DE|DG|DH|DL|DN|DT|DY|E|EC|EH|EN|EX|FK|FY|G|GL|GY|GU|HA|HD|HG|HP|HR|HS|HU|HX|IG|IM|IP|IV|JE|KA|KT|KW|KY|L|LA|LD|LE|LL|LN|LS|LU|M|ME|MK|ML|N|NE|NG|NN|NP|NR|NW|OL|OX|PA|PE|PH|PL|PO|PR|RG|RH|RM|S|SA|SE|SG|SK|SL|SM|SN|SO|SP|SR|SS|ST|SW|SY|TA|TD|TF|TN|TQ|TR|TS|TW|UB|W|WA|WC|WD|WF|WN|WR|WS|WV|YO|ZE)(\d[\dA-Z]?[ ]?\d[ABD-HJLN-UW-Z]{2}))$|^BFPO[ ]?\d{1,4}',
        'JE' => 'JE\d[\dA-Z]?[ ]?\d[ABD-HJLN-UW-Z]{2}',
        'GG' => 'GY\d[\dA-Z]?[ ]?\d[ABD-HJLN-UW-Z]{2}',
        'IM' => 'IM\d[\dA-Z]?[ ]?\d[ABD-HJLN-UW-Z]{2}',
        'US' => '\d{5}([ \-]\d{4})?',
        'CA' => '[ABCEGHJKLMNPRSTVXY]\d[ABCEGHJ-NPRSTV-Z][ ]?\d[ABCEGHJ-NPRSTV-Z]\d',
        'DE' => '\d{5}',
        'JP' => '\d{3}-\d{4}',
        'FR' => '(?!(0{2})|(9(6|9))[ ]?\d{3})(\d{2}[ ]?\d{3})',
        'AU' => '\d{4}',
        'IT' => '\d{5}',
        'CH' => '\d{4}',
        'AT' => '\d{4}',
        'ES' => '\d{5}',
        'NL' => '\d{4}[ ]?[A-Z]{2}',
        'BE' => '\d{4}',
        'DK' => '\d{4}',
        'SE' => '\d{3}[ ]?\d{2}',
        'NO' => '(?!0000)\d{4}',
        'BR' => '\d{5}[\-]?\d{3}',
        'PT' => '\d{4}([\-]\d{3})?',
        'FI' => '\d{5}',
        'AX' => '22\d{3}',
        'KR' => '\d{3}[\-]\d{3}',
        'CN' => '\d{6}',
        'TW' => '\d{3}(\d{2})?',
        'SG' => '\d{6}',
        'DZ' => '\d{5}',
        'AD' => 'AD\d{3}',
        'AR' => '([A-HJ-NP-Z])?\d{4}([A-Z]{3})?',
        'AM' => '(37)?\d{4}',
        'AZ' => '\d{4}',
        'BH' => '((1[0-2]|[2-9])\d{2})?',
        'BD' => '\d{4}',
        'BB' => '(BB\d{5})?',
        'BY' => '\d{6}',
        'BM' => '[A-Z]{2}[ ]?[A-Z0-9]{2}',
        'BA' => '\d{5}',
        'IO' => 'BBND 1ZZ',
        'BN' => '[A-Z]{2}[ ]?\d{4}',
        'BG' => '\d{4}',
        'KH' => '\d{5}',
        'CV' => '\d{4}',
        'CL' => '\d{7}',
        'CR' => '\d{4,5}|\d{3}-\d{4}',
        'HR' => '\d{5}',
        'CY' => '\d{4}',
        'CZ' => '\d{3}[ ]?\d{2}',
        'DO' => '\d{5}',
        'EC' => '([A-Z]\d{4}[A-Z]|(?:[A-Z]{2})?\d{6})?',
        'EG' => '\d{5}',
        'EE' => '\d{5}',
        'FO' => '\d{3}',
        'GE' => '\d{4}',
        'GR' => '\d{3}[ ]?\d{2}',
        'GL' => '39\d{2}',
        'GT' => '\d{5}',
        'HT' => '\d{4}',
        'HN' => '(?:\d{5})?',
        'HU' => '\d{4}',
        'IS' => '\d{3}',
        'IN' => '\d{6}',
        'ID' => '\d{5}',
        'IE' => '[\dA-Z]{3} ?[\dA-Z]{4}',
        'IL' => '\d{5}',
        'JO' => '\d{5}',
        'KZ' => '\d{6}',
        'KE' => '\d{5}',
        'KW' => '\d{5}',
        'LA' => '\d{5}',
        'LV' => '(LV-)?\d{4}',
        'LB' => '(\d{4}([ ]?\d{4})?)?',
        'LI' => '(948[5-9])|(949[0-8])',
        'LT' => '\d{5}',
        'LU' => '\d{4}',
        'MK' => '\d{4}',
        'MY' => '\d{5}',
        'MV' => '\d{5}',
        'MT' => '[A-Z]{3}[ ]?\d{2,4}',
        'MU' => '\d{5}',
        'MX' => '\d{5}',
        'MD' => '\d{4}',
        'MC' => '980\d{2}',
        'MA' => '\d{5}',
        'NP' => '\d{5}',
        'NZ' => '\d{4}',
        'NI' => '((\d{4}-)?\d{3}-\d{3}(-\d{1})?)?',
        'NG' => '(\d{6})?',
        'OM' => '(PC )?\d{3}',
        'PK' => '\d{5}',
        'PY' => '\d{4}',
        'PH' => '\d{4}',
        'PL' => '\d{2}-\d{3}',
        'PR' => '00[679]\d{2}([ \-]\d{4})?',
        'RO' => '\d{6}',
        'RU' => '\d{6}',
        'SM' => '4789\d',
        'SA' => '\d{5}',
        'SN' => '\d{5}',
        'SK' => '\d{3}[ ]?\d{2}',
        'SI' => '\d{4}',
        'ZA' => '\d{4}',
        'LK' => '\d{5}',
        'TJ' => '\d{6}',
        'TH' => '\d{5}',
        'TN' => '\d{4}',
        'TR' => '\d{5}',
        'TM' => '\d{6}',
        'UA' => '\d{5}',
        'UY' => '\d{5}',
        'UZ' => '\d{6}',
        'VA' => '00120',
        'VE' => '\d{4}',
        'ZM' => '\d{5}',
        'AS' => '96799',
        'CC' => '6799',
        'CK' => '\d{4}',
        'RS' => '\d{5}',
        'ME' => '8\d{4}',
        'CS' => '\d{5}',
        'YU' => '\d{5}',
        'CX' => '6798',
        'ET' => '\d{4}',
        'FK' => 'FIQQ 1ZZ',
        'NF' => '2899',
        'FM' => '(9694[1-4])([ \-]\d{4})?',
        'GF' => '9[78]3\d{2}',
        'GN' => '\d{3}',
        'GP' => '9[78][01]\d{2}',
        'GS' => 'SIQQ 1ZZ',
        'GU' => '969[123]\d([ \-]\d{4})?',
        'GW' => '\d{4}',
        'HM' => '\d{4}',
        'IQ' => '\d{5}',
        'KG' => '\d{6}',
        'LR' => '\d{4}',
        'LS' => '\d{3}',
        'MG' => '\d{3}',
        'MH' => '969[67]\d([ \-]\d{4})?',
        'MN' => '\d{6}',
        'MP' => '9695[012]([ \-]\d{4})?',
        'MQ' => '9[78]2\d{2}',
        'NC' => '988\d{2}',
        'NE' => '\d{4}',
        'VI' => '008(([0-4]\d)|(5[01]))([ \-]\d{4})?',
        'PF' => '987\d{2}',
        'PG' => '\d{3}',
        'PM' => '9[78]5\d{2}',
        'PN' => 'PCRN 1ZZ',
        'PW' => '96940',
        'RE' => '9[78]4\d{2}',
        'SH' => '(ASCN|STHL) 1ZZ',
        'SJ' => '\d{4}',
        'SO' => '\d{5}',
        'SZ' => '[HLMS]\d{3}',
        'TC' => 'TKCA 1ZZ',
        'WF' => '986\d{2}',
        'YT' => '976\d{2}',
        'VN' => '\d{6}',
        'VC' => 'VC\d{4}',
    ];

    /** @var array<string, string> */
    protected array $messageTemplates = [
        self::INVALID  => 'Invalid type given. String or integer expected',
        self::NO_MATCH => 'The input does not appear to be a postal code',
    ];

    /** @var key-of<self::POST_CODE_REGEX>|null */
    private readonly string|null $region;

    /**
     * Optional Manual postal code format
     *
     * @var non-empty-string|null
     */
    private readonly string|null $format;

    /** @param Options $options */
    public function __construct(array $options = [])
    {
        $locale       = $options['locale'] ?? null;
        $this->format = $options['format'] ?? null;

        if ($this->format === null && $locale === null) {
            throw new InvalidArgumentException('One of `format` or `locale` must be provided');
        }

        $region = null;
        if ($locale !== null && $this->format === null) {
            $region = Locale::getRegion($locale);
            if ($region === null || $region === '' || ! array_key_exists($region, self::POST_CODE_REGEX)) {
                throw new InvalidArgumentException(sprintf(
                    'The locale "%s" does not represent a known geographic region',
                    $locale,
                ));
            }
        }

        $this->region = $region;

        /** @psalm-suppress DocblockTypeContradiction Defensive checks */
        if ($this->format === '') {
            throw new InvalidArgumentException('Custom post code format patterns must be non-empty-string');
        }

        if ($this->format !== null) {
            $pattern = $this->resolvePattern();
            /** @psalm-suppress UnusedFunctionCall */
            @preg_match($pattern, 'anything');
            if (preg_last_error() !== PREG_NO_ERROR) {
                throw new InvalidArgumentException(sprintf(
                    'The format pattern "%s" is not a valid regex: %s',
                    $pattern,
                    preg_last_error_msg(),
                ));
            }
        }

        parent::__construct($options);
    }

    public function isValid(mixed $value): bool
    {
        if (! is_string($value) && ! is_int($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        if (! preg_match($this->resolvePattern(), (string) $value)) {
            $this->error(self::NO_MATCH);

            return false;
        }

        return true;
    }

    /** @return non-empty-string */
    private function resolvePattern(): string
    {
        if ($this->format !== null) {
            return $this->format;
        }

        assert($this->region !== null);

        return $this->normalisePattern(self::POST_CODE_REGEX[$this->region]);
    }

    /**
     * @param non-empty-string $pattern
     * @return non-empty-string
     */
    private function normalisePattern(string $pattern): string
    {
        if (! str_starts_with($pattern, '/^')) {
            $pattern = '/^' . $pattern;
        }

        if (! str_ends_with($pattern, '$/')) {
            $pattern .= '$/';
        }

        return $pattern;
    }
}
