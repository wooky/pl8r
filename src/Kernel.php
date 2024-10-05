<?php

declare(strict_types=1);

namespace Pl8r;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
  use MicroKernelTrait;
}
