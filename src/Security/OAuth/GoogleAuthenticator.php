<?php

declare(strict_types=1);

namespace Pl8r\Security\OAuth;

use Doctrine\ORM\EntityManagerInterface;
use Pl8r\Entity\Enum\AuthProvider;
use Pl8r\Entity\User;
use Pl8r\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Uid\Ulid;

final class GoogleAuthenticator extends AbstractAuthenticator
{
  private const string NEW_USER_ATTRIBUTE = 'is_new_user';
  private const int SUFFIX_LENGTH = 6;
  private const int PREFIX_LENGTH = User::MAX_USERNAME_LENGTH - self::SUFFIX_LENGTH - 1;

  public function __construct(
    private GoogleAuthProvider $provider,
    private UserRepository $userRepository,
    private EntityManagerInterface $entityManager,
    private UrlGeneratorInterface $urlGenerator,
  ) {}

  #[\Override]
  public function supports(Request $request): ?bool
  {
    return $request->getUriForPath($request->getPathInfo()) === $this->provider->routeUrl && 0 !== $request->query->count();
  }

  #[\Override]
  public function authenticate(Request $request): Passport
  {
    $details = $this->provider->getDetailsOrError($request);
    if (\is_string($details)) {
      throw new CustomUserMessageAuthenticationException($details);
    }

    $isNewUser = false;
    $user = $this->userRepository->findByAuthMethod(AuthProvider::Google, $details->oauthIdentifier);
    if (null === $user) {
      $isNewUser = true;
      $prefix = substr($details->email, 0, self::PREFIX_LENGTH);
      $suffix = substr((new Ulid())->toBase32(), -self::SUFFIX_LENGTH);
      $user = (new User())
        ->setUsername("{$prefix}-{$suffix}")
        ->setAuthMethod(AuthProvider::Google, $details->oauthIdentifier)
      ;
      $this->entityManager->persist($user);
      $this->entityManager->flush();

      $session = $request->getSession();
      \assert($session instanceof FlashBagAwareSessionInterface);
      $session->getFlashBag()->add('profile', true);
    }

    $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier()));
    $passport->setAttribute(self::NEW_USER_ATTRIBUTE, $isNewUser);

    return $passport;
  }

  #[\Override]
  public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
  {
    // return $token->getAttribute(self::NEW_USER_ATTRIBUTE) ? new RedirectResponse($this->urlGenerator->generate(MasterController::PROFILE_NAME)) : null;
    return null;
  }

  #[\Override]
  public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
  {
    // TODO?
    return null;
  }
}
