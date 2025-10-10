<?php

use PhpCsFixer\Finder;
use PhpCsFixer\Config;
use Tombenevides\DumpLinter\DumpRemovalFixer;

$finder = Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/migrations',
        __DIR__.'/tests',
    ])
;

$config = new Config();
$config->registerCustomFixers([
        new DumpRemovalFixer()
    ])
    ->setRules([
        '@Symfony' => true,
        'trailing_comma_in_multiline' => ['elements' => []],
        'Tombenevides/dump_removal' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;

return $config;