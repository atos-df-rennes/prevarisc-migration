<?php

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;
use FOS\JsRoutingBundle\FOSJsRoutingBundle;

return [
    FrameworkBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    WebProfilerBundle::class => ['dev' => true, 'test' => true],
    MonologBundle::class => ['all' => true],
    DebugBundle::class => ['dev' => true],
    MakerBundle::class => ['dev' => true],
    DoctrineBundle::class => ['all' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    TwigExtraBundle::class => ['all' => true],
    FOSJsRoutingBundle::class => ['all' => true],
];
