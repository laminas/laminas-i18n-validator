<?php

declare(strict_types=1);

namespace LaminasTest\I18n\Validator;

use IntlDateFormatter;
use Laminas\I18n\Validator\Alnum;
use Laminas\I18n\Validator\Alpha;
use Laminas\I18n\Validator\ConfigProvider;
use Laminas\I18n\Validator\DateTime;
use Laminas\I18n\Validator\IsFloat;
use Laminas\I18n\Validator\PostCode;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ConfigProvider as ValidatorConfigProvider;
use Laminas\Validator\ValidatorPluginManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_merge_recursive;
use function assert;
use function class_exists;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class ValidatorPluginManagerIntegrationTest extends TestCase
{
    private ServiceManager $container;

    protected function setUp(): void
    {
        $config = array_merge_recursive(
            (new ValidatorConfigProvider())->__invoke(),
            (new ConfigProvider())->__invoke(),
        );

        $dependencies = $config['dependencies'] ?? [];
        /** @psalm-suppress MixedAssignment */
        $dependencies['services'] ??= [];
        self::assertIsArray($dependencies['services']);
        $dependencies['services']['config'] = $config;

        /** @psalm-var ServiceManagerConfiguration $dependencies */
        $this->container = new ServiceManager($dependencies);
    }

    /** @return iterable<string, array{0: string, 1: class-string}> */
    public static function aliasProvider(): iterable
    {
        $aliases = (new ConfigProvider())->__invoke()['validators']['aliases'] ?? [];

        foreach ($aliases as $alias => $class) {
            assert(class_exists($class));

            yield $alias => [$alias, $class];
        }
    }

    /** @param class-string $class */
    #[DataProvider('aliasProvider')]
    public function testFiltersCanBeBuiltByThePluginManager(string $alias, string $class): void
    {
        $plugins = $this->container->get(ValidatorPluginManager::class);
        self::assertInstanceOf(ValidatorPluginManager::class, $plugins);

        self::assertInstanceOf(
            $class,
            $plugins->get($alias),
        );
    }

    public function testOptionsArePassedToPostCode(): void
    {
        $plugins = $this->container->get(ValidatorPluginManager::class);
        self::assertInstanceOf(ValidatorPluginManager::class, $plugins);

        $validator = $plugins->build(PostCode::class, ['format' => '/^[0-5]{3}z$/']);

        self::assertTrue($validator->isValid('132z'));
        self::assertFalse($validator->isValid('foo'));
    }

    public function testOptionsArePassedToDateTime(): void
    {
        $plugins = $this->container->get(ValidatorPluginManager::class);
        self::assertInstanceOf(ValidatorPluginManager::class, $plugins);

        $validator = $plugins->build(
            DateTime::class,
            ['dateType' => IntlDateFormatter::SHORT, 'locale' => 'en_GB'],
        );

        self::assertFalse($validator->isValid('2020-01-01'));
        self::assertTrue($validator->isValid('1/1/2020'));
    }

    public function testOptionsArePassedToIsFloat(): void
    {
        $plugins = $this->container->get(ValidatorPluginManager::class);
        self::assertInstanceOf(ValidatorPluginManager::class, $plugins);

        $validator = $plugins->build(IsFloat::class, ['locale' => 'de_DE']);

        self::assertTrue($validator->isValid('1.234,56'));
        self::assertFalse($validator->isValid('1,230.56'));
    }

    public function testOptionsArePassedToIsInt(): void
    {
        $plugins = $this->container->get(ValidatorPluginManager::class);
        self::assertInstanceOf(ValidatorPluginManager::class, $plugins);

        $validator = $plugins->build(IsFloat::class, ['locale' => 'de_DE']);

        self::assertTrue($validator->isValid('1.234'));
    }

    public function testOptionsArePassedToAlnum(): void
    {
        $plugins = $this->container->get(ValidatorPluginManager::class);
        self::assertInstanceOf(ValidatorPluginManager::class, $plugins);

        $validator = $plugins->build(Alnum::class, ['allowWhiteSpace' => true]);

        self::assertFalse($validator->isValid('!!abc 123!!'));
        self::assertTrue($validator->isValid('abc 123'));
    }

    public function testOptionsArePassedToAlpha(): void
    {
        $plugins = $this->container->get(ValidatorPluginManager::class);
        self::assertInstanceOf(ValidatorPluginManager::class, $plugins);

        $validator = $plugins->build(Alpha::class, ['allowWhiteSpace' => true]);

        self::assertFalse($validator->isValid('!!a b c 123!!'));
        self::assertTrue($validator->isValid('a b c '));
    }
}
