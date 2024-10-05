<?php

declare(strict_types=1);

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\Turbo\TurboBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

return [
  FrameworkBundle::class => ['all' => true],
  TwigBundle::class => ['all' => true],
  TwigExtraBundle::class => ['all' => true],
  MakerBundle::class => ['dev' => true],
  DoctrineBundle::class => ['all' => true],
  DoctrineMigrationsBundle::class => ['all' => true],
  WebProfilerBundle::class => ['dev' => true, 'test' => true],
  SecurityBundle::class => ['all' => true],
  TwigComponentBundle::class => ['all' => true],
  StimulusBundle::class => ['all' => true],
  TurboBundle::class => ['all' => true],
];
