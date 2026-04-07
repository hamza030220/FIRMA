<?php

namespace App\Service\Event;

use App\Entity\Event\Evenement;
use App\Entity\Event\Sponsor;
use App\Repository\Event\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;

class SponsorService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SponsorRepository $repo,
    ) {}

    // ── Catalogue CRUD ──

    /** Tous les sponsors du catalogue (non assignés). */
    public function getCatalog(): array
    {
        return $this->repo->findCatalog();
    }

    public function getById(int $id): ?Sponsor
    {
        return $this->repo->find($id);
    }

    /** Ajoute un sponsor au catalogue. */
    public function addToCatalog(Sponsor $sponsor): Sponsor
    {
        $sponsor->setEvenement(null);
        $this->em->persist($sponsor);
        $this->em->flush();

        return $sponsor;
    }

    /** Met à jour un sponsor du catalogue. */
    public function update(Sponsor $sponsor): Sponsor
    {
        $this->em->flush();
        return $sponsor;
    }

    /** Supprime un sponsor. */
    public function delete(Sponsor $sponsor): void
    {
        $this->em->remove($sponsor);
        $this->em->flush();
    }

    // ── Assignation à un événement ──

    /** Sponsors assignés à un événement. */
    public function getByEvenement(int $evenementId): array
    {
        return $this->repo->findByEvenement($evenementId);
    }

    /**
     * Clone un sponsor du catalogue et l'assigne à un événement.
     * Le sponsor original reste dans le catalogue.
     */
    public function assignerAEvenement(Sponsor $catalogSponsor, Evenement $evenement, ?string $montant = null): Sponsor
    {
        $clone = $catalogSponsor->cloneForEvent($evenement, $montant);
        $this->em->persist($clone);
        $this->em->flush();

        return $clone;
    }

    /**
     * Assigne plusieurs sponsors d'un coup (lors de la création/modif d'un événement).
     *
     * @param int[] $sponsorIds  IDs des sponsors du catalogue à cloner
     */
    public function assignerMultiple(array $sponsorIds, Evenement $evenement): void
    {
        foreach ($sponsorIds as $id) {
            $catalogSponsor = $this->repo->find($id);
            if ($catalogSponsor && $catalogSponsor->isCatalog()) {
                $clone = $catalogSponsor->cloneForEvent($evenement);
                $this->em->persist($clone);
            }
        }
        $this->em->flush();
    }

    /** Supprime tous les sponsors assignés à un événement. */
    public function deleteByEvenement(int $evenementId): void
    {
        $this->repo->deleteByEvenement($evenementId);
    }

    /**
     * Re-synchronise les sponsors d'un événement.
     * Supprime les anciens, clone les nouveaux sélectionnés.
     *
     * @param int[] $sponsorIds
     */
    public function syncForEvent(array $sponsorIds, Evenement $evenement): void
    {
        $this->deleteByEvenement($evenement->getIdEvenement());
        $this->assignerMultiple($sponsorIds, $evenement);
    }

    // ── Statistiques ──

    public function countAssignes(): int
    {
        return $this->repo->countAssignes();
    }

    public function totalContributions(): float
    {
        return $this->repo->totalContributions();
    }

    /** @return array<array{secteur: string, count: int}> */
    public function repartitionParSecteur(): array
    {
        return $this->repo->repartitionParSecteur();
    }

    /** @return array<array{nom: string, count: int}> */
    public function topSponsors(int $limit = 5): array
    {
        return $this->repo->topSponsors($limit);
    }
}
