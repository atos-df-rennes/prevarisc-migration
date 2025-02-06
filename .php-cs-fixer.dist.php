<?php

use PhpCsFixer\Finder;
use PhpCsFixer\Config;

$finder = Finder::create()
    ->in(__DIR__.'/src')
;

$config = new Config();
return $config->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
