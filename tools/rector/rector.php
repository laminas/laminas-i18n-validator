<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitSelfCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;

return RectorConfig::configure()
    ->withPhpSets(php82: true)
    ->withPaths([
        __DIR__ . '/../../src',
        __DIR__ . '/../../test',
    ])->withPreparedSets(
        codeQuality: true,
        typeDeclarations: true,
        phpunitCodeQuality: true,
    )->withRules([
        PreferPHPUnitSelfCallRector::class,
    ])->withSkip([
        PreferPHPUnitThisCallRector::class,
        YieldDataProviderRector::class,
    ]);
