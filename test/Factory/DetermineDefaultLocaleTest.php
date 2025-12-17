<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator\Factory;

use ArrayObject;
use Laminas\I18n\Validator\Factory\DetermineDefaultLocale;
use Laminas\ServiceManager\ServiceManager;
use Locale;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class DetermineDefaultLocaleTest extends TestCase
{
    /** @return list<array{0: iterable, 1: non-empty-string}> */
    public static function configHasLocaleProvider(): array
    {
        return [
            [
                ['locale' => 'en_GB'],
                'en_GB',
            ],
            [
                new ArrayObject(['locale' => 'en_GB']),
                'en_GB',
            ],
        ];
    }

    #[DataProvider('configHasLocaleProvider')]
    public function testConfiguredLocaleIsReturned(iterable $config, string $expect): void
    {
        $container = new ServiceManager([
            'services' => [
                'config' => $config,
            ],
        ]);

        self::assertSame($expect, DetermineDefaultLocale::fromConfigWithSystemFallback($container));
    }

    /** @return array<string, array{0: ServiceManagerConfiguration}> */
    public static function variousMissingLocaleConfigurations(): array
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
                ['services' => ['config' => ['locale' => null]]],
            ],
            'Empty Locale'   => [
                ['services' => ['config' => ['locale' => '']]],
            ],
        ];
    }

    /** @param ServiceManagerConfiguration $config */
    #[DataProvider('variousMissingLocaleConfigurations')]
    public function testSystemLocaleIsReturned(array $config): void
    {
        $system = Locale::getDefault();
        self::assertNotEmpty($system);

        $container = new ServiceManager($config);

        self::assertSame($system, DetermineDefaultLocale::fromConfigWithSystemFallback($container));
    }
}
