<?php

namespace App\Service\Event;

use App\Entity\Event\Evenement;
use App\Repository\Event\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;

class EvenementService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EvenementRepository $repo,
        private readonly GeocodingService $geocoder,
    ) {}

    /**
     * Liste triée par date de début.
     *
     * @return Evenement[]
     */
    public function getAll(): array
    {
        return $this->repo->findAllOrdered();
    }

    /**
     * Page paginée côté SQL.
     *
     * @return array{items: list<Evenement>, total: int}
     */
    public function getPaginated(int $page, int $limit, string $search = '', string $sort = 'date_asc'): array
    {
        return $this->repo->findPaginated($page, $limit, $search, $sort);
    }

    public function getById(int $id): ?Evenement
    {
        return $this->repo->find($id);
    }

    /**
     * Recherche admin (titre uniquement).
     *
     * @return Evenement[]
     */
    public function search(string $query): array
    {
        return $this->repo->search($query);
    }

    /**
     * Recherche user (titre, organisateur, lieu).
     *
     * @return Evenement[]
     */
    public function searchMulti(string $query): array
    {
        return $this->repo->searchMulti($query);
    }

    /** Crée un nouvel événement. */
    public function create(Evenement $evenement): Evenement
    {
        $this->geocode($evenement);
        $this->em->persist($evenement);
        $this->em->flush();

        return $evenement;
    }

    /** Met à jour un événement existant. */
    public function update(Evenement $evenement): Evenement
    {
        $this->geocode($evenement);
        $evenement->setDateModification(new \DateTime());
        $this->em->flush();

        return $evenement;
    }

    /**
     * Populate latitude/longitude from address fields when missing or empty.
     * Silently skips on failure (the entity stays valid without coordinates).
     */
    private function geocode(Evenement $evt): void
    {
        if ($evt->hasCoordinates()) {
            return;
        }
        $candidates = $this->geocoder->buildCandidates($evt->getAdresse(), $evt->getLieu());
        if ($candidates === []) {
            return;
        }
        $coords = $this->geocoder->geocodeBest($candidates);
        if ($coords !== null) {
            $evt->setLatitude((string) $coords['lat']);
            $evt->setLongitude((string) $coords['lng']);
        }
    }

    /** Supprime un événement. */
    public function delete(Evenement $evenement): void
    {
        $this->em->remove($evenement);
        $this->em->flush();
    }

    /** Change le statut d'un événement (annuler, terminer…). */
    public function updateStatut(Evenement $evenement, string $statut): void
    {
        $evenement->setStatut($statut);
        $evenement->setDateModification(new \DateTime());
        $this->em->flush();
    }

    /**
     * Réserve des places de manière atomique.
     * @throws \RuntimeException si pas assez de places
     */
    public function reserverPlaces(int $evenementId, int $nb): void
    {
        if (!$this->repo->reserverPlaces($evenementId, $nb)) {
            throw new \RuntimeException('Pas assez de places disponibles.');
        }
        $this->em->clear();
    }

    /** Libère des places (annulation, modification). */
    public function libererPlaces(int $evenementId, int $nb): void
    {
        $this->repo->libererPlaces($evenementId, $nb);
        $this->em->clear();
    }
}
