<?php

namespace App\Repository\Marketplace;

use App\Entity\Marketplace\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicule>
 */
class VehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicule::class);
    }

    /**
     * @param array<string, string> $orderBy
     * @return list<Vehicule>
     */
    public function findAllWithRelations(array $orderBy = ['dateCreation' => 'DESC']): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.categorie', 'c')->addSelect('c');

        foreach ($orderBy as $field => $dir) {
            $qb->addOrderBy('v.' . $field, $dir);
        }

        /** @var list<Vehicule> */
        return $qb->getQuery()->getResult();
    }

    /** @return list<Vehicule> */
    public function findAvailableWithRelations(): array
    {
        /** @var list<Vehicule> */
        return $this->createQueryBuilder('v')
            ->leftJoin('v.categorie', 'c')->addSelect('c')
            ->where('v.disponible = true')
            ->orderBy('v.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Vehicule> */
    public function findAvailablePaginated(int $page, int $limit = 12): array
    {
        /** @var list<Vehicule> */
        return $this->createQueryBuilder('v')
            ->leftJoin('v.categorie', 'c')->addSelect('c')
            ->where('v.disponible = true')
            ->orderBy('v.dateCreation', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAvailable(): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.disponible = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
