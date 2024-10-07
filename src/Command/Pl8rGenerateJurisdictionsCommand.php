<?php

declare(strict_types=1);

namespace Pl8r\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
  name: 'pl8r:generate-jurisdictions',
)]
class Pl8rGenerateJurisdictionsCommand extends Command
{
  private ?SymfonyStyle $io = null;
  private string $svgPath = '';

  /** @var array<string,\DOMDocument> */ private array $svgCollections = [];

  public function __construct(
    private Filesystem $filesystem,
    private SerializerInterface $serializer,
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

    $jurisdictions = $this->serializer->deserialize(
      $this->filesystem->readFile('./misc/jurisdictions/jurisdictions.csv'),
      JurisdictionCsvLine::class.'[]',
      'csv'
    );
    \assert(\is_array($jurisdictions));
    foreach ($jurisdictions as $jurisdiction) {
      \assert($jurisdiction instanceof JurisdictionCsvLine);
      $idComponents = explode(string: $jurisdiction->id, separator: '-');
      if (isset($idComponents[1])) {
        $parent = $idComponents[0];
        $child = $idComponents[1];
      } else {
        $parent = null;
        $child = $idComponents[0];
      }

      $iso = \strlen($jurisdiction->flag) >= 2 ? $jurisdiction->flag : $jurisdiction->id;
      $this->addToSvg($parent, $child, $iso);
    }

    $assetsDir = Path::join('assets', 'generated', 'jurisdictions');
    $this->filesystem->remove($assetsDir);
    $this->filesystem->mkdir($assetsDir);
    foreach ($this->svgCollections as $key => $doc) {
      $svgPath = Path::join($assetsDir, "{$key}.svg");
      if (false === $doc->save($svgPath)) {
        $error = libxml_get_last_error() ? libxml_get_last_error()->message : 'unknown';
        $this->io->error(["Failed to save {$svgPath}", $error]);

        return Command::FAILURE;
      }
    }

    return Command::SUCCESS;
  }

  private function addToSvg(?string $parent, string $child, string $iso): void
  {
    $flagPath = Path::join($this->svgPath, "{$iso}.svg");
    $svgDoc = new \DOMDocument();
    if (!$svgDoc->load($flagPath, LIBXML_COMPACT | LIBXML_NOBLANKS)) {
      $error = libxml_get_last_error() ? libxml_get_last_error()->message : 'unknown';
      $this->io?->writeln("<comment>Cannot read {$flagPath}: {$error}</comment>");

      return;
    }
    $svg = $svgDoc->firstElementChild;
    \assert(null !== $svg && 'svg' === $svg->nodeName);

    $parent ??= 'global';
    if (!isset($this->svgCollections[$parent])) {
      $doc = new \DOMDocument();
      $doc->loadXML('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs></defs></svg>');
      $this->svgCollections[$parent] = $doc;
    } else {
      $doc = $this->svgCollections[$parent];
    }
    $defs = $doc->firstElementChild?->firstElementChild;
    \assert(null !== $defs);

    $viewBox = $svg->getAttribute('viewBox') ?: "0 0 {$svg->getAttribute('width')} {$svg->getAttribute('height')}";
    $symbol = $doc->createElement('symbol');
    $symbol->setAttribute('id', $child);
    $symbol->setAttribute('viewBox', $viewBox);
    while (isset($svg->childNodes[0])) {
      $childNode = $svg->childNodes[0];
      \assert($childNode instanceof \DOMNode);
      $doc->adoptNode($childNode);
      $symbol->appendChild($childNode);
    }
    $defs->appendChild($symbol);
  }
}
