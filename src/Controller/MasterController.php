<?php

declare(strict_types=1);

namespace Pl8r\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MasterController extends AbstractController
{
  public const string INDEX_NAME = 'app_index';

  /**
   * @psalm-suppress PossiblyUnusedMethod ??? why ???
   */
  #[Route('/', name: self::INDEX_NAME)]
  public function index(): Response
  {
    return $this->render('index.html.twig', [
      'controller_name' => 'IndexController',
    ]);
  }
}
