<?php

declare(strict_types=1);

namespace Pl8r\Security\OAuth;

final readonly class OAuthDetails
{
  public function __construct(
    public string $oauthIdentifier,
    public string $email,
  ) {}
}
