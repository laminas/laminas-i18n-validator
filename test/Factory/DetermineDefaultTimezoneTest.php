<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator\Factory;

use ArrayObject;
use Laminas\I18n\Validator\Factory\DetermineDefaultTimezone;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function date_default_timezone_get;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class DetermineDefaultTimezoneTest extends TestCase
{
    /** @return list<array{0: iterable, 1: non-empty-string}> */
    public static function configHasTimeZoneProvider(): array
    {
        return [
            [
                ['timezone' => 'Europe/London'],
                'Europe/London',
            ],
            [
                new ArrayObject(['timezone' => 'Europe/London']),
                'Europe/London',
            ],
        ];
    }

    #[DataProvider('configHasTimeZoneProvider')]
    public function testConfiguredTimeZoneIsReturned(iterable $config, string $expect): void
    {
        $container = new ServiceManager([
            'services' => [
                'config' => $config,
            ],
        ]);

        self::assertSame($expect, DetermineDefaultTimezone::fromConfigWithSystemFallback($container));
    }

    /** @return array<string, array{0: ServiceManagerConfiguration}> */
    public static function variousMissingTimeZoneConfigurations(): array
    {
        return [
            'No config'      => [
                ['services' => []],
            ],
            'Empty Config'   => [
                ['services' => ['config' => []]],
            ],
            'Empty Iterable' => [
                ['services' => ['config' => new ArrayObject([])]],
            ],
            'Null Locale'    => [
                ['services' => ['config' => ['timezone' => null]]],
            ],
            'Empty Locale'   => [
                ['services' => ['config' => ['timezone' => '']]],
            ],
        ];
    }

    /** @param ServiceManagerConfiguration $config */
    #[DataProvider('variousMissingTimeZoneConfigurations')]
    public function testSystemTimeZoneIsReturned(array $config): void
    {
        $system = date_default_timezone_get();

        $container = new ServiceManager($config);

        self::assertSame($system, DetermineDefaultTimezone::fromConfigWithSystemFallback($container));
    }
}
