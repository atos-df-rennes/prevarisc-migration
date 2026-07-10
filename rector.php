<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;
use Rector\ValueObject\PhpVersion;

const RECTOR_TARGET_SYMFONY_SET = SymfonySetList::SYMFONY_54;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withRootFiles()
    ->withPhpVersion(PhpVersion::PHP_74)
    ->withPhpSets(php74: true)
    ->withSets([RECTOR_TARGET_SYMFONY_SET])
    ->withSkip([
        __DIR__.'/src/Dto/Historique/DonneeHistorique.php',
        __DIR__.'/src/Dto/Historique/LastAvis.php',
    ])
    ->withTypeCoverageLevel(50)
    ->withDeadCodeLevel(50)
    ->withCodeQualityLevel(50)
    ->withImportNames(removeUnusedImports: true, importShortClasses: false)
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
