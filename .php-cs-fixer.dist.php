<?php

use PhpCsFixer\Finder;
use PhpCsFixer\Config;

$finder = Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/migrations',
        __DIR__.'/tests',
    ])
;

$config = new Config();
return $config->setRules([
        '@Symfony' => true,
        'trailing_comma_in_multiline' => ['elements' => []]
    ])
    ->setFinder($finder)
;
