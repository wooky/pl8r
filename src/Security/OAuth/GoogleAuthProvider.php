<?php

declare(strict_types=1);

namespace Pl8r\Security\OAuth;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class GoogleAuthProvider
{
  public const string ROUTE_NAME = 'app_login_google';
  public const string OAUTH2_STATE_ATTRIBUTE = 'oauth2state';

  public string $routeUrl;
  private Google $provider;

  public function __construct(
    private LoggerInterface $logger,
    UrlGeneratorInterface $urlGenerator,
    #[Autowire(env: 'GOOGLE_CLIENT_ID')]
    string $clientId,
    #[Autowire(env: 'GOOGLE_CLIENT_SECRET')]
    string $clientSecret,
  ) {
    $this->routeUrl = $urlGenerator->generate(self::ROUTE_NAME, referenceType: UrlGeneratorInterface::ABSOLUTE_URL);
    $this->provider = new Google([
      'clientId' => $clientId,
      'clientSecret' => $clientSecret,
      'redirectUri' => $this->routeUrl,
    ]);
  }

  public function redirectToProvider(Request $request): Response
  {
    $authUrl = $this->provider->getAuthorizationUrl();
    $session = $request->getSession();
    \assert($session instanceof FlashBagAwareSessionInterface);
    $session->getFlashBag()->add(self::OAUTH2_STATE_ATTRIBUTE, $this->provider->getState());

    return new RedirectResponse($authUrl);
  }

  public function getDetailsOrError(Request $request): OAuthDetails|string
  {
    if ('' !== ($error = $request->query->getString('error'))) {
      $this->logger->error('Got error from Google OAuth', ['error' => $error]);

      return 'Got error from Google OAuth';
    }

    if ('' === ($code = $request->query->getString('code'))) {
      return 'Code was not provided';
    }

    $session = $request->getSession();
    \assert($session instanceof FlashBagAwareSessionInterface);
    $actualStates = $session->getFlashBag()->get(self::OAUTH2_STATE_ATTRIBUTE);
    if (!isset($actualStates[0])) {
      return 'OAuth state not set';
    }

    if ('' === ($state = $request->query->getString('state')) || $state !== $actualStates[0]) {
      $this->logger->error('OAuth states do not match', ['expected' => $state, 'actual' => $actualStates[0]]);

      return 'OAuth states do not match';
    }

    try {
      $token = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
      \assert($token instanceof AccessToken);
      $ownerDetails = $this->provider->getResourceOwner($token);
      \assert($ownerDetails instanceof GoogleUser);
      $email = $ownerDetails->getEmail();
      if (null === $email) {
        return 'User has no email';
      }

      return new OAuthDetails(
        (string) $ownerDetails->getId(),
        $email,
      );
    } catch (IdentityProviderException $e) {
      $this->logger->error('Error getting resource owner', ['e' => $e]);

      return 'Error getting resource owner';
    }
  }
}
