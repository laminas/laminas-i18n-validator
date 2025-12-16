<?php

declare(strict_types=1);

namespace Laminas\I18n\Validator\Factory;

use Laminas\I18n\Validator\IsFloat;
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
 * @psalm-import-type Options from IsFloat
 */
final readonly class IsFloatFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): IsFloat {
        /** @psalm-var Options $options No runtime validation here */
        $options = array_merge([
            'locale' => DetermineDefaultLocale::fromConfigWithSystemFallback($container),
        ], $options ?? []);

        return new IsFloat($options);
    }
}
