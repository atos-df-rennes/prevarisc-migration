<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withRootFiles()
    ->withPhpVersion(PhpVersion::PHP_71)
    ->withPhpSets(php71: true)
    ->withTypeCoverageLevel(50)
    ->withDeadCodeLevel(50)
    ->withCodeQualityLevel(50)
    ->withImportNames(removeUnusedImports: true)
    ->withPreparedSets(
        codingStyle: true,
        privatization: true,
        earlyReturn: true,
        symfonyConfigs: true,
        symfonyCodeQuality: true,
    )
    ->withComposerBased(
        doctrine: true,
        twig: true,
        symfony: true,
    )
;
