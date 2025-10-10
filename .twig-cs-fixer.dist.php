<?php

use App\Linter\TwigCsFixer\NoDumpRule;
use TwigCsFixer\Config\Config;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$ruleset = new Ruleset();
$ruleset->addStandard(new TwigCsFixer());
$ruleset->addRule(new NoDumpRule());

$config = new Config();
$config->setRuleset($ruleset);
$config->allowNonFixableRules();

return $config;
