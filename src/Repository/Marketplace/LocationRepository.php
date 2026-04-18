<?php

namespace App\Repository\Marketplace;

use App\Entity\Marketplace\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function findAllWithRelations(array $orderBy = ['dateReservation' => 'DESC']): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.utilisateur', 'u')->addSelect('u')
            ->leftJoin('l.vehicule', 'v')->addSelect('v')
            ->leftJoin('l.terrain', 't')->addSelect('t');

        foreach ($orderBy as $field => $dir) {
            $qb->addOrderBy('l.' . $field, $dir);
        }

        return $qb->getQuery()->getResult();
    }
}
