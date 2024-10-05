<?php

declare(strict_types=1);

namespace Pl8r\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Pl8r\Entity\Enum\AuthProvider;
use Pl8r\Entity\User;
use Pl8r\Form\RegistrationFormType;
use Pl8r\Security\OAuth\GoogleAuthProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
  private const string LOGIN_NAME = 'app_login';

  #[Route('/login', name: self::LOGIN_NAME)]
  public function login(AuthenticationUtils $authenticationUtils): Response
  {
    if (null !== $this->getUser()) {
      return $this->redirectToRoute(MasterController::INDEX_NAME);
    }
    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername();

    return $this->render('login.html.twig', [
      'last_username' => $lastUsername,
      'error' => $error,
    ]);
  }

  #[Route('/login/google', name: GoogleAuthProvider::ROUTE_NAME)]
  public function loginGoogle(
    Request $request,
    GoogleAuthProvider $authProvider,
    AuthenticationUtils $authenticationUtils,
  ): Response {
    if (null !== $this->getUser()) {
      return $this->redirectToRoute(MasterController::INDEX_NAME);
    }

    if (null !== $authenticationUtils->getLastAuthenticationError(clearSession: false)) {
      return $this->redirectToRoute(self::LOGIN_NAME);
    }

    return $authProvider->redirectToProvider($request);
  }

  #[Route('/register', name: 'app_register')]
  public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
  {
    $user = new User();
    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $plainPassword = $form->get('plainPassword')->getData();
      \assert(\is_string($plainPassword));

      // encode the plain password
      $user->setAuthMethod(AuthProvider::Password, $userPasswordHasher->hashPassword($user, $plainPassword));

      $entityManager->persist($user);
      $entityManager->flush();

      // do anything else you need here, like send an email

      return $security->login($user, 'form_login', 'main') ?? throw new \Exception('Unable to log in');
    }

    return $this->render('register.html.twig', [
      'registrationForm' => $form,
    ]);
  }
}
