<?php

use App\Linter\TwigCsFixer\NoDumpRule;
use TwigCsFixer\Config\Config;
use TwigCsFixer\Rules\Function\NamedArgumentSeparatorRule;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$ruleset = new Ruleset();
$ruleset->addStandard(new TwigCsFixer());
$ruleset->addRule(new NoDumpRule());

// Non compatible avec Twig 2.x
$ruleset->removeRule(NamedArgumentSeparatorRule::class);

$config = new Config();
$config->setRuleset($ruleset);
$config->allowNonFixableRules();

return $config;
