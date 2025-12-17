<?php

declare(strict_types=1);

namespace Laminas\I18n\Validator\Factory;

use Laminas\I18n\Validator\DateTime;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

use function array_merge;

/**
 * This class is internal and as such is not subject to any backwards compatibility guarantees.
 *
 * @internal
 *
 * @psalm-internal \Laminas\I18n\Validator
 * @psalm-internal \LaminasTest\I18n\Validator
 * @psalm-import-type Options from DateTime
 */
final readonly class DateTimeFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): DateTime {
        /** @psalm-var Options $options No runtime validation here */
        $options = array_merge([
            'locale'   => DetermineDefaultLocale::fromConfigWithSystemFallback($container),
            'timezone' => DetermineDefaultTimezone::fromConfigWithSystemFallback($container),
        ], $options ?? []);

        return new DateTime($options);
    }
}
