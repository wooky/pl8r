<?php

declare(strict_types=1);

namespace Pl8r\Command;

final readonly class JurisdictionCsvLine
{
  public function __construct(
    public string $id,
    public string $vri,
    public string $flag
  ) {}
}
