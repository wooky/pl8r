<?php

declare(strict_types=1);

namespace Pl8r\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pl8r\Entity\Enum\AuthProvider;
use Pl8r\Entity\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, User::class);
  }

  /**
   * Used to upgrade (rehash) the user's password automatically over time.
   */
  #[\Override]
  public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
  {
    if (!$user instanceof User) {
      throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
    }

    $user->setAuthMethod(AuthProvider::Password, $newHashedPassword);
    $this->getEntityManager()->persist($user);
    $this->getEntityManager()->flush();
  }

  public function findByAuthMethod(AuthProvider $authProvider, string $value): ?User
  {
    $qb = $this->createQueryBuilder('u');
    $result = $qb
      ->where('JSON_CONTAINS(u.authMethods, ?1, ?2) = 1')
      ->setParameter(1, json_encode($value))
      ->setParameter(2, "$.{$authProvider->value}")
      ->getQuery()
      ->getOneOrNullResult()
    ;
    \assert(null === $result || $result instanceof User);

    return $result;
  }

  //    /**
  //     * @return User[] Returns an array of User objects
  //     */
  //    public function findByExampleField($value): array
  //    {
  //        return $this->createQueryBuilder('u')
  //            ->andWhere('u.exampleField = :val')
  //            ->setParameter('val', $value)
  //            ->orderBy('u.id', 'ASC')
  //            ->setMaxResults(10)
  //            ->getQuery()
  //            ->getResult()
  //        ;
  //    }

  //    public function findOneBySomeField($value): ?User
  //    {
  //        return $this->createQueryBuilder('u')
  //            ->andWhere('u.exampleField = :val')
  //            ->setParameter('val', $value)
  //            ->getQuery()
  //            ->getOneOrNullResult()
  //        ;
  //    }
}
