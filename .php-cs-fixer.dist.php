<?php

$finder = (new PhpCsFixer\Finder())
  ->in(__DIR__)
  ->exclude('var');

return (new PhpCsFixer\Config())
  ->setRules([
    '@PhpCsFixer' => true,
    '@PhpCsFixer:risky' => true,
    '@PHP83Migration' => true,
    '@PHP80Migration:risky' => true,
  ])
  ->setFinder($finder)
  ->setIndent('  ')
  ->setRiskyAllowed(true)
;
