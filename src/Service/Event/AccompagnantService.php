<?php

namespace App\Service\Event;

use App\Entity\Event\Accompagnant;
use App\Repository\Event\AccompagnantRepository;
use Doctrine\ORM\EntityManagerInterface;

class AccompagnantService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AccompagnantRepository $repo,
    ) {}

    /** Accompagnants d'une participation. */
    public function getByParticipation(int $participationId): array
    {
        return $this->repo->findByParticipation($participationId);
    }

    /** Accompagnants d'un événement (toutes participations confondues). */
    public function getByEvenement(int $evenementId): array
    {
        return $this->repo->findByEvenement($evenementId);
    }

    /** Nombre total d'accompagnants en base. */
    public function countAll(): int
    {
        return $this->repo->countAll();
    }

    /** Ajoute un accompagnant et persiste. */
    public function add(Accompagnant $accompagnant): void
    {
        $this->em->persist($accompagnant);
        $this->em->flush();
    }

    /** Supprime un accompagnant. */
    public function remove(Accompagnant $accompagnant): void
    {
        $this->em->remove($accompagnant);
        $this->em->flush();
    }
}
