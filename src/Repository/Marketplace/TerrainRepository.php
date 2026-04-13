<?php

namespace App\Repository\Marketplace;

use App\Entity\Marketplace\Terrain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Terrain>
 */
class TerrainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Terrain::class);
    }

    public function findAllWithRelations(array $orderBy = ['dateCreation' => 'DESC']): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.categorie', 'c')->addSelect('c');

        foreach ($orderBy as $field => $dir) {
            $qb->addOrderBy('t.' . $field, $dir);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAvailableWithRelations(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.categorie', 'c')->addSelect('c')
            ->where('t.disponible = true')
            ->orderBy('t.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailablePaginated(int $page, int $limit = 12): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.categorie', 'c')->addSelect('c')
            ->where('t.disponible = true')
            ->orderBy('t.dateCreation', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAvailable(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.disponible = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
