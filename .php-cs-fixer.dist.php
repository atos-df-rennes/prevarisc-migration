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
        // PHP 7.1 : `mixed` n'est pas un type natif, le docblock @param/@return mixed
        // est nécessaire pour PHPStan niveau 10 et ne doit pas être supprimé.
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true, 'allow_hidden_params' => true, 'remove_inheritdoc' => true],
        // Conserver /** @var */ même avant un return (utilisé pour affiner les types
        // pour PHPStan, e.g. json_decode() renvoie mixed).
        'phpdoc_to_comment' => ['ignored_tags' => ['var']],
        'trailing_comma_in_multiline' => ['elements' => []],
        'Tombenevides/dump_removal' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;

return $config;