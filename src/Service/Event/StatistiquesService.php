<?php

namespace App\Service\Event;

use App\Repository\Event\AccompagnantRepository;
use App\Repository\Event\EvenementRepository;
use App\Repository\Event\ParticipationRepository;
use App\Repository\Event\SponsorRepository;

class StatistiquesService
{
    public function __construct(
        private readonly EvenementRepository $evenementRepo,
        private readonly ParticipationRepository $participationRepo,
        private readonly AccompagnantRepository $accompagnantRepo,
        private readonly SponsorRepository $sponsorRepo,
    ) {}

    /**
     * Retourne toutes les stats du dashboard en un seul appel.
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        return [
            // KPIs
            'totalEvenements'       => $this->evenementRepo->countAll(),
            'evenementsActifs'      => $this->evenementRepo->countActifs(),
            'totalParticipants'     => $this->participationRepo->countTotalParticipants(),
            'confirmees'            => $this->participationRepo->countConfirmees(),
            'enAttente'             => $this->participationRepo->countEnAttente(),
            'tauxRemplissage'       => $this->evenementRepo->tauxRemplissageMoyen(),
            'cetteSemaine'          => $this->evenementRepo->countCetteSemaine(),
            'sponsorsAssignes'      => $this->sponsorRepo->countAssignes(),

            // Charts
            'repartitionParType'    => $this->evenementRepo->repartitionParType(),
            'repartitionParStatut'  => $this->evenementRepo->repartitionParStatut(),
            'topEvenements'         => $this->evenementRepo->topEvenements(),
            'placesDisponibles'     => $this->evenementRepo->evenementsPlacesDisponibles(),
            'evenementsParMois'     => $this->evenementRepo->evenementsParMois(),
            'participationsParMois' => $this->participationRepo->participationsParMois(),
            'repartParticStatut'    => $this->participationRepo->repartitionParStatut(),
            'ceMois'                => $this->evenementRepo->countCeMois(),
            'totalAccompagnants'    => $this->accompagnantRepo->countAll(),

            // Sponsors
            'repartitionParSecteur' => $this->sponsorRepo->repartitionParSecteur(),
            'topSponsors'           => $this->sponsorRepo->topSponsors(),
            'totalContributions'    => $this->sponsorRepo->totalContributions(),
        ];
    }

    // ── Méthodes individuelles (si besoin hors dashboard) ──

    public function countEvenements(): int
    {
        return $this->evenementRepo->countAll();
    }

    public function countActifs(): int
    {
        return $this->evenementRepo->countActifs();
    }

    public function countTotalParticipants(): int
    {
        return $this->participationRepo->countTotalParticipants();
    }

    public function tauxRemplissage(): float
    {
        return $this->evenementRepo->tauxRemplissageMoyen();
    }
}
