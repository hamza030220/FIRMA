<?php

namespace App\Repository\Marketplace;

use App\Entity\Marketplace\Equipement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipement>
 */
class EquipementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipement::class);
    }

    public function findAllWithRelations(array $orderBy = ['dateCreation' => 'DESC']): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.categorie', 'c')->addSelect('c')
            ->leftJoin('e.fournisseur', 'f')->addSelect('f');

        foreach ($orderBy as $field => $dir) {
            $qb->addOrderBy('e.' . $field, $dir);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAvailableWithRelations(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.categorie', 'c')->addSelect('c')
            ->leftJoin('e.fournisseur', 'f')->addSelect('f')
            ->where('e.disponible = true')
            ->orderBy('e.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailablePaginated(int $page, int $limit = 12): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.categorie', 'c')->addSelect('c')
            ->leftJoin('e.fournisseur', 'f')->addSelect('f')
            ->where('e.disponible = true')
            ->orderBy('e.dateCreation', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAvailable(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.disponible = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLowStock(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.quantiteStock < e.seuilAlerte')
            ->getQuery()
            ->getResult();
    }
}
