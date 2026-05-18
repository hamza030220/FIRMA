<?php

namespace App\Controller;

use App\Repository\Event\AccompagnantRepository;
use App\Repository\Event\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TicketController extends AbstractController
{
    #[Route('/ticket/{code}', name: 'ticket_show', methods: ['GET'], requirements: ['code' => '[A-Z0-9\-]+'])]
    public function show(
        string $code,
        ParticipationRepository $participationRepo,
        AccompagnantRepository $accompagnantRepo,
    ): Response {
        $code = strtoupper(trim($code));

        $participation = null;
        $accompagnant  = null;
        $holderName    = null;
        $ticketType    = 'Principal';

        if (str_starts_with($code, 'PART-')) {
            $participation = $participationRepo->findByCode($code);
            if ($participation) {
                $user       = $participation->getUtilisateur();
                $holderName = $user ? trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? '')) : 'Participant';
            }
        } elseif (str_starts_with($code, 'ACC-')) {
            $accompagnant  = $accompagnantRepo->findOneBy(['codeAccompagnant' => $code]);
            $participation = $accompagnant?->getParticipation();
            $holderName    = $accompagnant ? trim(($accompagnant->getPrenom() ?? '') . ' ' . ($accompagnant->getNom() ?? '')) : null;
            $ticketType    = 'Accompagnant';
        }

        if (!$participation) {
            throw $this->createNotFoundException('Ticket introuvable pour le code ' . $code);
        }

        return $this->render('ticket/show.html.twig', [
            'code'          => $code,
            'participation' => $participation,
            'evenement'     => $participation->getEvenement(),
            'holderName'    => $holderName ?: 'Participant',
            'ticketType'    => $ticketType,
        ]);
    }
}
