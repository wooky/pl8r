<?php

declare(strict_types=1);

namespace Pl8r\Entity;

use Doctrine\ORM\Mapping as ORM;
use Pl8r\Entity\Enum\AuthProvider;
use Pl8r\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
  public const int MAX_USERNAME_LENGTH = 64;

  #[ORM\Id]
  #[ORM\Column(type: UlidType::NAME, unique: true)]
  #[ORM\GeneratedValue(strategy: 'CUSTOM')]
  #[ORM\CustomIdGenerator(class: 'doctrine.ulid_generator')]
  private ?Ulid $id = null;

  #[ORM\Column(length: self::MAX_USERNAME_LENGTH)]
  private ?string $username = null;

  /**
   * @var list<string> The user roles
   */
  #[ORM\Column]
  private array $roles = [];

  /**
   * @var array<value-of<AuthProvider>,string>
   */
  #[ORM\Column]
  private array $authMethods = [];

  /**
   * @psalm-suppress PossiblyUnusedMethod TODO
   */
  public function getId(): ?Ulid
  {
    return $this->id;
  }

  /**
   * @psalm-suppress PossiblyUnusedMethod TODO
   */
  public function getUsername(): ?string
  {
    return $this->username;
  }

  public function setUsername(string $username): static
  {
    $this->username = $username;

    return $this;
  }

  /**
   * A visual identifier that represents this user.
   *
   * @see UserInterface
   */
  #[\Override]
  public function getUserIdentifier(): string
  {
    return (string) $this->username;
  }

  /**
   * @see UserInterface
   *
   * @return array<string>
   */
  #[\Override]
  public function getRoles(): array
  {
    $roles = $this->roles;
    // guarantee every user at least has ROLE_USER
    $roles[] = 'ROLE_USER';

    return array_unique($roles);
  }

  /**
   * @param list<string> $roles
   *
   * @psalm-suppress PossiblyUnusedMethod TODO
   */
  public function setRoles(array $roles): static
  {
    $this->roles = $roles;

    return $this;
  }

  /**
   * @see UserInterface
   */
  #[\Override]
  public function eraseCredentials(): void
  {
    // If you store any temporary, sensitive data on the user, clear it here
    // $this->plainPassword = null;
  }

  /**
   * @return array<value-of<AuthProvider>,string>
   *
   * @psalm-suppress PossiblyUnusedMethod TODO
   */
  public function getAuthMethods(): array
  {
    return $this->authMethods;
  }

  public function getAuthMethod(AuthProvider $authProvider): ?string
  {
    $ap = $authProvider->value;

    return $this->authMethods[$ap] ?? null;
  }

  public function setAuthMethod(AuthProvider $authProvider, string $value): static
  {
    $this->authMethods[$authProvider->value] = $value;

    return $this;
  }

  /**
   * @see PasswordAuthenticatedUserInterface
   */
  #[\Override]
  public function getPassword(): ?string
  {
    return $this->getAuthMethod(AuthProvider::Password);
  }

  #[\Override]
  public function isEqualTo(UserInterface $user): bool
  {
    return ($user instanceof self) && $this->id?->equals($user->id);
  }
}
