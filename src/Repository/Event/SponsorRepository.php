<?php

namespace App\Repository\Event;

use App\Entity\Event\Sponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sponsor>
 */
class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

    /** Tous les sponsors triés par nom. */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Sponsors du catalogue (non assignés à un événement). */
    public function findCatalog(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.evenement IS NULL')
            ->orderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Sponsors assignés à un événement. */
    public function findByEvenement(int $evenementId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.evenement', 'e')
            ->andWhere('e.idEvenement = :eid')
            ->setParameter('eid', $evenementId)
            ->orderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Supprime tous les sponsors assignés à un événement. */
    public function deleteByEvenement(int $evenementId): void
    {
        $this->createQueryBuilder('s')
            ->delete()
            ->where('s.evenement = :eid')
            ->setParameter('eid', $evenementId)
            ->getQuery()
            ->execute();
    }

    // ── Statistiques ──

    public function countAssignes(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.idSponsor)')
            ->where('s.evenement IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function totalContributions(): float
    {
        $result = $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT COALESCE(SUM(montant_contribution), 0) FROM sponsors"
        );

        return (float) $result;
    }

    /** @return array<array{secteur: string, count: int}> */
    public function repartitionParSecteur(): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT secteur_activite AS secteur, COUNT(*) AS count FROM sponsors GROUP BY secteur_activite"
        );
    }

    /** @return array<array{nom: string, count: int}> */
    public function topSponsors(int $limit = 5): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT nom, COUNT(*) AS count FROM sponsors WHERE id_evenement IS NOT NULL GROUP BY nom ORDER BY count DESC LIMIT :lim",
            ['lim' => $limit],
            ['lim' => \Doctrine\DBAL\ParameterType::INTEGER]
        );
    }
}
