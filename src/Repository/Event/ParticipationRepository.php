<?php

namespace App\Repository\Event;

use App\Entity\Event\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    /** Participations d'un événement. */
    public function findByEvenement(int $evenementId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.evenement = :eid')
            ->setParameter('eid', $evenementId)
            ->orderBy('p.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Participations actives (non annulées) d'un utilisateur. */
    public function findActiveByUser(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.utilisateur', 'u')
            ->andWhere('u.id = :uid')
            ->andWhere('p.statut IN (:statuts)')
            ->setParameter('uid', $userId)
            ->setParameter('statuts', ['confirme', 'en_attente'])
            ->orderBy('p.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Vérifie si un utilisateur participe déjà à un événement. */
    public function isUserAlreadyParticipating(int $userId, int $evenementId): bool
    {
        $count = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.idParticipation)')
            ->join('p.utilisateur', 'u')
            ->join('p.evenement', 'e')
            ->andWhere('u.id = :uid')
            ->andWhere('e.idEvenement = :eid')
            ->andWhere('p.statut IN (:statuts)')
            ->setParameter('uid', $userId)
            ->setParameter('eid', $evenementId)
            ->setParameter('statuts', ['confirme', 'en_attente'])
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /** Trouve une participation par utilisateur + événement. */
    public function findByUserAndEvent(int $userId, int $evenementId): ?Participation
    {
        return $this->createQueryBuilder('p')
            ->join('p.utilisateur', 'u')
            ->join('p.evenement', 'e')
            ->andWhere('u.id = :uid')
            ->andWhere('e.idEvenement = :eid')
            ->setParameter('uid', $userId)
            ->setParameter('eid', $evenementId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Trouve par code de participation. */
    public function findByCode(string $code): ?Participation
    {
        return $this->findOneBy(['codeParticipation' => $code]);
    }

    /** Nombre de participations confirmées pour un événement. */
    public function countConfirmedByEvent(int $evenementId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.idParticipation)')
            ->join('p.evenement', 'e')
            ->andWhere('e.idEvenement = :eid')
            ->andWhere('p.statut = :s')
            ->setParameter('eid', $evenementId)
            ->setParameter('s', 'confirme')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Total des personnes (participants + accompagnants) pour un événement confirmé. */
    public function countTotalPersonnesByEvent(int $evenementId): int
    {
        $result = $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT COALESCE(SUM(nombre_accompagnants), 0) + COUNT(*)
             FROM participations
             WHERE id_evenement = :eid AND statut = 'confirme'",
            ['eid' => $evenementId]
        );

        return (int) $result;
    }

    /**
     * Détails des participants avec infos utilisateur (pour le listing admin).
     * @return array<array{participation: Participation, nom: string, prenom: string, email: string}>
     */
    public function findParticipantsDetailsByEvent(int $evenementId): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.utilisateur', 'u')
            ->join('p.evenement', 'e')
            ->addSelect('u')
            ->andWhere('e.idEvenement = :eid')
            ->setParameter('eid', $evenementId)
            ->orderBy('p.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ── Statistiques ──

    public function countConfirmees(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.idParticipation)')
            ->where('p.statut = :s')->setParameter('s', 'confirme')
            ->getQuery()->getSingleScalarResult();
    }

    public function countEnAttente(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.idParticipation)')
            ->where('p.statut = :s')->setParameter('s', 'en_attente')
            ->getQuery()->getSingleScalarResult();
    }

    public function countTotalParticipants(): int
    {
        $result = $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT COALESCE(SUM(nombre_accompagnants), 0) + COUNT(*) FROM participations WHERE statut = 'confirme'"
        );

        return (int) $result;
    }

    /** @return array<array{statut: string, count: int}> */
    public function repartitionParStatut(): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT statut, COUNT(*) AS count FROM participations GROUP BY statut"
        );
    }

    /** @return array<array{mois: string, count: int}> */
    public function participationsParMois(): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT DATE_FORMAT(date_inscription, '%Y-%m') AS mois, COUNT(*) AS count
             FROM participations
             WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY mois ORDER BY mois"
        );
    }
}
