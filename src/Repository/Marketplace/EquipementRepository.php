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

    /**
     * @param array<string, string> $orderBy
     * @return list<Equipement>
     */
    public function findAllWithRelations(array $orderBy = ['dateCreation' => 'DESC']): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.categorie', 'c')->addSelect('c')
            ->leftJoin('e.fournisseur', 'f')->addSelect('f');

        foreach ($orderBy as $field => $dir) {
            $qb->addOrderBy('e.' . $field, $dir);
        }

        /** @var list<Equipement> */
        return $qb->getQuery()->getResult();
    }

    /** @return list<Equipement> */
    public function findAvailableWithRelations(): array
    {
        /** @var list<Equipement> */
        return $this->createQueryBuilder('e')
            ->leftJoin('e.categorie', 'c')->addSelect('c')
            ->leftJoin('e.fournisseur', 'f')->addSelect('f')
            ->where('e.disponible = true')
            ->orderBy('e.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Equipement> */
    public function findAvailablePaginated(int $page, int $limit = 12): array
    {
        /** @var list<Equipement> */
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

    /** @return list<Equipement> */
    public function findLowStock(): array
    {
        /** @var list<Equipement> */
        return $this->createQueryBuilder('e')
            ->where('e.quantiteStock < e.seuilAlerte')
            ->getQuery()
            ->getResult();
    }
}
