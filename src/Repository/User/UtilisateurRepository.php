<?php

namespace App\Repository\User;

use App\Entity\User\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setMotDePasse($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return string[]
     */
    public function findAdminEmails(): array
    {
        $rows = $this->createQueryBuilder('u')
            ->select('u.email')
            ->andWhere('u.typeUser = :type')
            ->andWhere('u.email IS NOT NULL')
            ->setParameter('type', 'admin')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_unique(array_filter(array_map(
            static fn (array $row): ?string => isset($row['email']) && $row['email'] !== '' ? (string) $row['email'] : null,
            $rows
        ))));
    }

    /**
     * @return string[]
     */
    public function findClientEmails(): array
    {
        $rows = $this->createQueryBuilder('u')
            ->select('u.email')
            ->andWhere('u.typeUser = :type')
            ->andWhere('u.email IS NOT NULL')
            ->setParameter('type', 'client')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_unique(array_filter(array_map(
            static fn (array $row): ?string => isset($row['email']) && $row['email'] !== '' ? (string) $row['email'] : null,
            $rows
        ))));
    }
}
