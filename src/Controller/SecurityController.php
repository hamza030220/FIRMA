<?php

namespace App\Controller;

use App\Service\Event\ParticipationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly ParticipationService $participationService,
    ) {}

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('user_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/participation/{id}/confirmer/{token}', name: 'public_participation_confirm', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function confirmParticipation(int $id, string $token): Response
    {
        $participation = $this->participationService->getById($id);
        if (!$participation) {
            return $this->render('security/confirmation.html.twig', [
                'status'  => 'error',
                'title'   => 'Participation introuvable',
                'message' => 'Cette participation n\'existe pas ou a été supprimée.',
            ]);
        }

        if (!$this->participationService->verifyToken($participation, $token)) {
            return $this->render('security/confirmation.html.twig', [
                'status'  => 'error',
                'title'   => 'Lien invalide',
                'message' => 'Le lien de confirmation est invalide ou a expiré.',
            ]);
        }

        $eventName = $participation->getEvenement()?->getTitre();

        if ($participation->getStatut() !== 'en_attente') {
            return $this->render('security/confirmation.html.twig', [
                'status'    => 'already',
                'eventName' => $eventName,
            ]);
        }

        $this->participationService->confirmer($participation);

        return $this->render('security/confirmation.html.twig', [
            'status'    => 'success',
            'eventName' => $eventName,
        ]);
    }
}
