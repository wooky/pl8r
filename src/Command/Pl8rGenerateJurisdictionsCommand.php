<?php

declare(strict_types=1);

namespace Pl8r\Command;

use Pl8r\Component\Jurisdiction\JurisdictionRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
  name: 'pl8r:generate-jurisdictions',
)]
class Pl8rGenerateJurisdictionsCommand extends Command
{
  private ?SymfonyStyle $io = null;
  private string $svgPath = '';

  public function __construct(
    private Filesystem $filesystem,
    private JurisdictionRepository $jurisdictionRepository,
  ) {
    parent::__construct();
  }

  #[\Override]
  protected function configure(): void
  {
    $this
      ->addArgument('regions-flags', InputArgument::REQUIRED, 'Path to fonttools/region-flags repository')
    ;
  }

  #[\Override]
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $this->io = new SymfonyStyle($input, $output);
    libxml_use_internal_errors(true);

    $regionFlags = (string) $input->getArgument('regions-flags');
    $this->svgPath = Path::join($regionFlags, 'svg');
    if (!$this->filesystem->exists($this->svgPath)) {
      $this->io->error("{$this->svgPath} does not exist");

      return Command::FAILURE;
    }

    $assetsDir = Path::join('assets', 'generated', 'jurisdictions');
    $this->filesystem->remove($assetsDir);
    $this->filesystem->mkdir($assetsDir);

    $globalCollection = null;
    foreach ($this->jurisdictionRepository->jurisdictions as $countryCode => $country) {
      $globalCollection = $this->addToSvg($globalCollection, $countryCode, $countryCode);

      if (isset($country[JurisdictionRepository::KEY_COUNTRY_SUBDIVISIONS])) {
        $localCollection = null;
        foreach ($country[JurisdictionRepository::KEY_COUNTRY_SUBDIVISIONS] as $subdivisionCode => $subdivision) {
          $iso = $subdivision[JurisdictionRepository::KEY_SUBDIVISION_USE_ISO] ? $subdivisionCode : "{$countryCode}-{$subdivisionCode}";
          $localCollection = $this->addToSvg($localCollection, $subdivisionCode, $iso);
        }
        self::saveSvgCollection($localCollection, $countryCode, $assetsDir);
      }
    }
    self::saveSvgCollection($globalCollection, 'global', $assetsDir);

    return Command::SUCCESS;
  }

  private function addToSvg(?\DOMDocument $svgCollection, string $child, string $iso): ?\DOMDocument
  {
    $flagPath = Path::join($this->svgPath, "{$iso}.svg");
    $svgDoc = new \DOMDocument();
    if (!$svgDoc->load($flagPath, LIBXML_COMPACT | LIBXML_NOBLANKS)) {
      $error = libxml_get_last_error() ? libxml_get_last_error()->message : 'unknown';
      $this->io?->writeln("<comment>Cannot read {$flagPath}: {$error}</comment>");

      return $svgCollection;
    }
    $svg = $svgDoc->firstElementChild;
    \assert(null !== $svg && 'svg' === $svg->nodeName);

    if (null === $svgCollection) {
      $svgCollection = new \DOMDocument();
      $svgCollection->loadXML('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs></defs></svg>');
    }
    $defs = $svgCollection->firstElementChild?->firstElementChild;
    \assert(null !== $defs);

    $viewBox = $svg->getAttribute('viewBox') ?: "0 0 {$svg->getAttribute('width')} {$svg->getAttribute('height')}";
    $symbol = $svgCollection->createElement('symbol');
    $symbol->setAttribute('id', $child);
    $symbol->setAttribute('viewBox', $viewBox);
    while (isset($svg->childNodes[0])) {
      $childNode = $svg->childNodes[0];
      \assert($childNode instanceof \DOMNode);
      $svgCollection->adoptNode($childNode);
      $symbol->appendChild($childNode);
    }
    $defs->appendChild($symbol);

    return $svgCollection;
  }

  private static function saveSvgCollection(?\DOMDocument $svgCollection, string $code, string $assetsDir): void
  {
    if (null === $svgCollection) {
      return;
    }

    $svgPath = Path::join($assetsDir, "{$code}.svg");
    if (false === $svgCollection->save($svgPath)) {
      $error = libxml_get_last_error() ? libxml_get_last_error()->message : 'unknown';

      throw new \Exception("Failed to save {$svgPath}: {$error}");
    }
  }
}
