<?php

use TwigCsFixer\Config\Config;
use TwigCsFixer\Rules\Variable\VariableNameRule;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$ruleset = new Ruleset();
$ruleset->addStandard(new TwigCsFixer());

$ruleset->removeRule(VariableNameRule::class);

$config = new Config();
$config->setRuleset($ruleset);

return $config;