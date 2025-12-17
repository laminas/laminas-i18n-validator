<?php

declare(strict_types=1);

namespace Laminas\I18n\Validator\Factory;

use Locale;
use Psr\Container\ContainerInterface;

use function assert;
use function is_iterable;
use function is_string;
use function iterator_to_array;

/**
 * This class is internal and as such is not subject to any backwards compatibility guarantees.
 *
 * @internal
 *
 * @psalm-internal \Laminas\I18n\Validator
 * @psalm-internal \LaminasTest\I18n\Validator
 */
final readonly class DetermineDefaultLocale
{
    /** @return non-empty-string */
    public static function fromConfigWithSystemFallback(ContainerInterface $container): string
    {
        /** @psalm-var mixed $config */
        $config = $container->has('config') ? $container->get('config') : [];
        $config = is_iterable($config) ? iterator_to_array($config) : [];

        /** @psalm-var mixed $configuredLocale */
        $configuredLocale = $config['locale'] ?? null;
        if (is_string($configuredLocale) && $configuredLocale !== '') {
            return $configuredLocale;
        }

        $system = Locale::getDefault();
        assert($system !== '');

        return $system;
    }
}
