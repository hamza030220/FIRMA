<?php

namespace App\Controller;

use App\Entity\User\Utilisateur;
use App\Form\User\InscriptionType;
use App\Repository\User\UtilisateurRepository;
use App\Repository\Event\AccompagnantRepository;
use App\Service\Event\ParticipationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly ParticipationService $participationService,
        private readonly AccompagnantRepository $accompagnantRepo,
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

    #[Route('/inscription', name: 'app_inscription')]
    public function inscription(
        Request $request,
        UserPasswordHasherInterface $hasher,
        UtilisateurRepository $repo,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('user_dashboard');
        }

        $form = $this->createForm(InscriptionType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Vérifier email unique
            if ($repo->findOneBy(['email' => $data['email']])) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->render('security/signup.html.twig', ['form' => $form]);
            }

            $utilisateur = new Utilisateur();
            $utilisateur->setNom($data['nom']);
            $utilisateur->setPrenom($data['prenom']);
            $utilisateur->setEmail($data['email']);
            $utilisateur->setTelephone($data['telephone'] ?? null);
            $utilisateur->setAdresse($data['adresse'] ?? null);
            $utilisateur->setVille($data['ville'] ?? null);
            $utilisateur->setTypeUser($data['typeUser']);
            $utilisateur->setDateCreation(new \DateTime());

            $motDePasse = $form->get('motDePasse')->getData();
            $hashed = $hasher->hashPassword($utilisateur, $motDePasse);
            $utilisateur->setMotDePasse($hashed);

            $em = $repo->getEntityManager();
            $em->persist($utilisateur);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('security/signup.html.twig', ['form' => $form]);
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

    #[Route('/ticket/{code}', name: 'public_ticket', requirements: ['code' => '[A-Z0-9\-]+'], methods: ['GET'])]
    public function viewTicket(string $code): Response
    {
        // Try participation code first
        $participation = $this->participationService->getByCode($code);
        if ($participation && $participation->getStatut() === 'confirme') {
            return $this->render('security/ticket.html.twig', [
                'type'          => 'participant',
                'participation' => $participation,
                'user'          => $participation->getUtilisateur(),
                'evenement'     => $participation->getEvenement(),
                'accompagnant'  => null,
            ]);
        }

        // Try accompagnant code
        $accompagnant = $this->accompagnantRepo->findOneBy(['codeAccompagnant' => $code]);
        if ($accompagnant) {
            $part = $accompagnant->getParticipation();
            if ($part && $part->getStatut() === 'confirme') {
                return $this->render('security/ticket.html.twig', [
                    'type'          => 'accompagnant',
                    'participation' => $part,
                    'user'          => null,
                    'evenement'     => $part->getEvenement(),
                    'accompagnant'  => $accompagnant,
                ]);
            }
        }

        return $this->render('security/ticket.html.twig', [
            'type'          => 'invalid',
            'participation' => null,
            'user'          => null,
            'evenement'     => null,
            'accompagnant'  => null,
        ]);
    }
}
