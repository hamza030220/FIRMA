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

    /**
     * Find locations that overlap with a given date range for a specific vehicle or terrain.
     * Only considers active statuses (not cancelled/terminated).
     */
    public function findOverlapping(string $type, int $itemId, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.typeLocation = :type')
            ->andWhere('l.statut NOT IN (:excludedStatuts)')
            ->andWhere('l.dateDebut < :end')
            ->andWhere('l.dateFin > :start')
            ->setParameter('type', $type)
            ->setParameter('excludedStatuts', ['annulee', 'terminee'])
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($type === 'vehicule') {
            $qb->andWhere('l.vehicule = :itemId');
        } else {
            $qb->andWhere('l.terrain = :itemId');
        }
        $qb->setParameter('itemId', $itemId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all booked date ranges for a specific vehicle or terrain (current & future only).
     * Returns array of ['dateDebut' => ..., 'dateFin' => ...].
     */
    public function findBookedRanges(string $type, int $itemId): array
    {
        $qb = $this->createQueryBuilder('l')
            ->select('l.dateDebut, l.dateFin')
            ->where('l.typeLocation = :type')
            ->andWhere('l.statut NOT IN (:excludedStatuts)')
            ->andWhere('l.dateFin >= :today')
            ->setParameter('type', $type)
            ->setParameter('excludedStatuts', ['annulee', 'terminee'])
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('l.dateDebut', 'ASC');

        if ($type === 'vehicule') {
            $qb->andWhere('l.vehicule = :itemId');
        } else {
            $qb->andWhere('l.terrain = :itemId');
        }
        $qb->setParameter('itemId', $itemId);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get all current & future locations with relations (for admin calendar).
     */
    public function findCurrentAndFutureWithRelations(): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.utilisateur', 'u')->addSelect('u')
            ->leftJoin('l.vehicule', 'v')->addSelect('v')
            ->leftJoin('l.terrain', 't')->addSelect('t')
            ->where('l.dateFin >= :today')
            ->andWhere('l.statut NOT IN (:excludedStatuts)')
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('excludedStatuts', ['annulee'])
            ->orderBy('l.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
