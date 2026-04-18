<?php

namespace App\Repository\Event;

use App\Entity\Event\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /** Tous les événements triés par date de début (ASC). */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Recherche par titre (LIKE insensible à la casse). */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->where('LOWER(e.titre) LIKE :q')
            ->setParameter('q', '%' . mb_strtolower($query) . '%')
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Recherche multi-champs (titre, organisateur, lieu) pour le front user. */
    public function searchMulti(string $query): array
    {
        $q = '%' . mb_strtolower($query) . '%';

        return $this->createQueryBuilder('e')
            ->where('LOWER(e.titre) LIKE :q')
            ->orWhere('LOWER(e.organisateur) LIKE :q')
            ->orWhere('LOWER(e.lieu) LIKE :q')
            ->setParameter('q', $q)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Met à jour le statut d'un événement. */
    public function updateStatut(int $id, string $statut): void
    {
        $this->createQueryBuilder('e')
            ->update()
            ->set('e.statut', ':statut')
            ->set('e.dateModification', ':now')
            ->where('e.idEvenement = :id')
            ->setParameter('statut', $statut)
            ->setParameter('now', new \DateTime())
            ->setParameter('id', $id)
            ->getQuery()
            ->execute();
    }

    /**
     * Réserve des places (décrémente places_disponibles).
     * @return bool true si la réservation a réussi
     */
    public function reserverPlaces(int $id, int $nb): bool
    {
        $rows = $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE evenements SET places_disponibles = places_disponibles - :nb WHERE id_evenement = :id AND places_disponibles >= :nb',
            ['nb' => $nb, 'id' => $id]
        );

        return $rows > 0;
    }

    /** Libère des places (incrémente places_disponibles). */
    public function libererPlaces(int $id, int $nb): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE evenements SET places_disponibles = places_disponibles + :nb WHERE id_evenement = :id',
            ['nb' => $nb, 'id' => $id]
        );
    }

    // ── Statistiques ──

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.idEvenement)')
            ->getQuery()->getSingleScalarResult();
    }

    public function countActifs(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.idEvenement)')
            ->where('e.statut = :s')->setParameter('s', 'actif')
            ->getQuery()->getSingleScalarResult();
    }

    public function tauxRemplissageMoyen(): float
    {
        $result = $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT ROUND(AVG(CASE WHEN capacite_max > 0 THEN ((capacite_max - places_disponibles) * 100.0 / capacite_max) ELSE 0 END), 1) FROM evenements"
        );

        return (float) ($result ?? 0);
    }

    /** @return array<array{type: string, count: int}> */
    public function repartitionParType(): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT type_evenement AS type, COUNT(*) AS count FROM evenements GROUP BY type_evenement"
        );
    }

    /** @return array<array{statut: string, count: int}> */
    public function repartitionParStatut(): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT statut, COUNT(*) AS count FROM evenements GROUP BY statut"
        );
    }

    /** @return array<array{mois: string, count: int}> */
    public function evenementsParMois(): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT DATE_FORMAT(date_debut, '%Y-%m') AS mois, COUNT(*) AS count
             FROM evenements
             WHERE date_debut >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY mois ORDER BY mois"
        );
    }

    /** @return array<array{titre: string, count: int}> */
    public function topEvenements(int $limit = 5): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT e.titre, COUNT(p.id_participation) AS count
             FROM evenements e
             LEFT JOIN participations p ON e.id_evenement = p.id_evenement AND p.statut = 'confirme'
             GROUP BY e.id_evenement, e.titre
             ORDER BY count DESC LIMIT :lim",
            ['lim' => $limit],
            ['lim' => \Doctrine\DBAL\ParameterType::INTEGER]
        );
    }

    /** @return array<array{titre: string, places: int}> */
    public function evenementsPlacesDisponibles(int $limit = 5): array
    {
        return $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT titre, places_disponibles AS places FROM evenements WHERE statut = 'actif' ORDER BY places_disponibles DESC LIMIT :lim",
            ['lim' => $limit],
            ['lim' => \Doctrine\DBAL\ParameterType::INTEGER]
        );
    }

    public function countCetteSemaine(): int
    {
        return (int) $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT COUNT(*) FROM evenements WHERE date_debut BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
        );
    }

    public function countCeMois(): int
    {
        return (int) $this->getEntityManager()->getConnection()->fetchOne(
            "SELECT COUNT(*) FROM evenements WHERE MONTH(date_debut) = MONTH(CURDATE()) AND YEAR(date_debut) = YEAR(CURDATE())"
        );
    }
}
