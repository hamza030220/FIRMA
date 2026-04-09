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
     * Recherche et filtre les utilisateurs par mot-clé et rôle.
     *
     * @return Utilisateur[]
     */
    public function searchAndFilter(string $keyword = '', string $role = ''): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.dateCreation', 'DESC');

        if ($keyword !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.nom', ':kw'),
                    $qb->expr()->like('u.prenom', ':kw'),
                    $qb->expr()->like('u.email', ':kw'),
                    $qb->expr()->like('u.telephone', ':kw'),
                    $qb->expr()->like('u.ville', ':kw'),
                )
            )->setParameter('kw', '%' . $keyword . '%');
        }

        if ($role !== '') {
            $qb->andWhere('u.typeUser = :role')
               ->setParameter('role', $role);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les utilisateurs par type.
     *
     * @return array<string, int>
     */
    public function countByType(): array
    {
        $rows = $this->createQueryBuilder('u')
            ->select('u.typeUser AS type, COUNT(u.id) AS total')
            ->groupBy('u.typeUser')
            ->getQuery()
            ->getArrayResult();

        $result = ['client' => 0, 'technicien' => 0, 'admin' => 0];
        foreach ($rows as $row) {
            $result[$row['type']] = (int) $row['total'];
        }

        return $result;
    }
}
