<?php

declare(strict_types=1);

namespace Pl8r\Controller;

use Pl8r\Component\Profile\EditUsername;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

final class ProfileController extends AbstractController
{
  public const PROFILE_NAME = 'app_profile';

  #[Route('/profile', name: self::PROFILE_NAME)]
  public function profile(): Response
  {
    return $this->render('Profile/index.html.twig');
  }

  #[Route('/profile/edit-username', name: 'app_profile_edit_username', condition: 'request.getPreferredFormat() === "'.TurboBundle::STREAM_FORMAT.'"')]
  public function editUsername(Request $request, EditUsername $component): Response
  {
    return $component->editUsername($request);
  }
}
