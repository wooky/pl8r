<?php

declare(strict_types=1);

namespace Pl8r\Entity\Enum;

enum AuthProvider: int
{
  case Password = 0;
  case Google = 1;
}
