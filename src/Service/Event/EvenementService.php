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
    ) {}

    /** Liste triée par date de début. */
    public function getAll(): array
    {
        return $this->repo->findAllOrdered();
    }

    public function getById(int $id): ?Evenement
    {
        return $this->repo->find($id);
    }

    /** Recherche admin (titre uniquement). */
    public function search(string $query): array
    {
        return $this->repo->search($query);
    }

    /** Recherche user (titre, organisateur, lieu). */
    public function searchMulti(string $query): array
    {
        return $this->repo->searchMulti($query);
    }

    /** Crée un nouvel événement. */
    public function create(Evenement $evenement): Evenement
    {
        $this->em->persist($evenement);
        $this->em->flush();

        return $evenement;
    }

    /** Met à jour un événement existant. */
    public function update(Evenement $evenement): Evenement
    {
        $evenement->setDateModification(new \DateTime());
        $this->em->flush();

        return $evenement;
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
        $this->em->clear(Evenement::class);
    }

    /** Libère des places (annulation, modification). */
    public function libererPlaces(int $evenementId, int $nb): void
    {
        $this->repo->libererPlaces($evenementId, $nb);
        $this->em->clear(Evenement::class);
    }
}
