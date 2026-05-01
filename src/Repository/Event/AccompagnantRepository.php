<?php

namespace App\Repository\Event;

use App\Entity\Event\Accompagnant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Accompagnant>
 */
class AccompagnantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Accompagnant::class);
    }

    /**
     * Accompagnants d'une participation.
     * @return list<Accompagnant>
     */
    public function findByParticipation(int $participationId): array
    {
        /** @var list<Accompagnant> */
        return $this->createQueryBuilder('a')
            ->join('a.participation', 'p')
            ->andWhere('p.idParticipation = :pid')
            ->setParameter('pid', $participationId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Accompagnants d'un événement (via participations).
     * @return list<Accompagnant>
     */
    public function findByEvenement(int $evenementId): array
    {
        /** @var list<Accompagnant> */
        return $this->createQueryBuilder('a')
            ->join('a.participation', 'p')
            ->join('p.evenement', 'e')
            ->andWhere('e.idEvenement = :eid')
            ->setParameter('eid', $evenementId)
            ->getQuery()
            ->getResult();
    }

    /** Nombre total d'accompagnants. */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.idAccompagnant)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
