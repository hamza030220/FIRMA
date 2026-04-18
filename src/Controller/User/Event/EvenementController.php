<?php

namespace App\Controller\User\Event;

use App\Entity\Event\Accompagnant;
use App\Form\Event\ParticipationType;
use App\Service\Event\EvenementService;
use App\Service\Event\ParticipationService;
use App\Repository\Event\ParticipationRepository;
use App\Repository\Event\SponsorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;

#[Route('/user/evenements')]
#[IsGranted('ROLE_USER')]
class EvenementController extends AbstractController
{
    public function __construct(
        private readonly EvenementService $evenementService,
        private readonly ParticipationService $participationService,
        private readonly ParticipationRepository $participationRepo,
        private readonly SponsorRepository $sponsorRepo,
        private readonly Packages $packages,
    ) {}

    /** Liste des événements avec recherche et tri. */
    #[Route('', name: 'user_evenements')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $sort   = $request->query->get('sort', 'date_asc');

        $evenements = $search
            ? $this->evenementService->searchMulti($search)
            : $this->evenementService->getAll();

        // Tri
        usort($evenements, match ($sort) {
            'date_desc'  => fn($a, $b) => $b->getDateDebut() <=> $a->getDateDebut(),
            'titre_asc'  => fn($a, $b) => strcasecmp($a->getTitre(), $b->getTitre()),
            'places'     => fn($a, $b) => $b->getPlacesDisponibles() <=> $a->getPlacesDisponibles(),
            default      => fn($a, $b) => $a->getDateDebut() <=> $b->getDateDebut(),
        });

        // Vérifier participations de l'user connecté
        $user = $this->getUser();
        $userParticipations = [];
        if ($user) {
            foreach ($evenements as $evt) {
                $userParticipations[$evt->getIdEvenement()] =
                    $this->participationRepo->isUserAlreadyParticipating($user->getId(), $evt->getIdEvenement());
            }
        }

        // Detect LAN IP for QR codes (so phones on same WiFi can scan)
        $lanBaseUrl = $request->getSchemeAndHttpHost();
        $host = $request->getHost();
        if (in_array($host, ['127.0.0.1', 'localhost', '::1'])) {
            $ipOutput = shell_exec('ipconfig');
            if ($ipOutput && preg_match('/Wi-Fi[\s\S]*?IPv4[^:]+:\s*([\d.]+)/', $ipOutput, $m)) {
                $lanBaseUrl = $request->getScheme() . '://' . $m[1] . ':' . $request->getPort();
            }
        }

        return $this->render('user/event/index.html.twig', [
            'evenements'         => $evenements,
            'search'             => $search,
            'sort'               => $sort,
            'userParticipations' => $userParticipations,
            'lanBaseUrl'         => $lanBaseUrl,
        ]);
    }

    // ──────────────────────────────────────────
    //  JSON — event detail for modal
    // ──────────────────────────────────────────
    #[Route('/{id}/json', name: 'user_evenement_json', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function eventJson(int $id): JsonResponse
    {
        $evt = $this->evenementService->getById($id);
        if (!$evt) {
            return $this->json(['error' => 'Événement introuvable'], 404);
        }

        $sponsors = $this->sponsorRepo->findByEvenement($id);
        $user = $this->getUser();
        $participation = $user
            ? $this->participationRepo->findByUserAndEvent($user->getId(), $id)
            : null;
        $confirmedCount = $this->participationRepo->countConfirmedByEvent($id);
        $pct = $evt->getCapaciteMax() > 0
            ? round(($evt->getCapaciteMax() - $evt->getPlacesDisponibles()) / $evt->getCapaciteMax() * 100)
            : 0;

        $sponsorList = [];
        foreach ($sponsors as $s) {
            $sponsorList[] = [
                'nom'     => $s->getNom(),
                'logoUrl' => $s->getLogoUrl(),
                'secteur' => $s->getSecteurEnum()?->value,
            ];
        }

        return $this->json([
            'id'               => $evt->getIdEvenement(),
            'titre'            => $evt->getTitre(),
            'description'      => $evt->getDescription(),
            'imageUrl'         => $evt->getImageUrl() ? $this->packages->getUrl($evt->getImageUrl()) : null,
            'type'             => $evt->getTypeEnum()?->label(),
            'statut'           => $evt->getStatutEnum()?->label(),
            'statutBadge'      => $evt->getStatutEnum()?->badgeClass(),
            'statutRaw'        => $evt->getStatut(),
            'dateDebut'        => $evt->getDateDebut()?->format('d/m/Y'),
            'dateFin'          => $evt->getDateFin()?->format('d/m/Y'),
            'horaireDebut'     => $evt->getHoraireDebut()?->format('H:i'),
            'horaireFin'       => $evt->getHoraireFin()?->format('H:i'),
            'lieu'             => $evt->getLieu(),
            'adresse'          => $evt->getAdresse(),
            'googleMapsUrl'    => $evt->getGoogleMapsUrl(),
            'organisateur'     => $evt->getOrganisateur(),
            'contactEmail'     => $evt->getContactEmail(),
            'contactTel'       => $evt->getContactTel(),
            'capaciteMax'      => $evt->getCapaciteMax(),
            'placesDisponibles'=> $evt->getPlacesDisponibles(),
            'pctRemplissage'   => $pct,
            'confirmedCount'   => $confirmedCount,
            'sponsors'         => $sponsorList,
            'participation'    => $participation ? [
                'id'     => $participation->getIdParticipation(),
                'code'   => $participation->getStatut() === 'confirme' ? $participation->getCodeParticipation() : null,
                'statut' => $participation->getStatut(),
                'date'   => $participation->getDateInscription()?->format('d/m/Y H:i'),
                'accompagnants' => $participation->getNombreAccompagnants(),
                'accompagnantsList' => array_map(fn($a) => [
                    'nom' => $a->getNom(),
                    'prenom' => $a->getPrenom(),
                ], $participation->getAccompagnants()->toArray()),
                'commentaire'   => $participation->getCommentaire(),
            ] : null,
        ]);
    }

    // ──────────────────────────────────────────
    //  POST — Participer à un événement
    // ──────────────────────────────────────────
    #[Route('/{id}/participer', name: 'user_evenement_participer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function participer(int $id, Request $request): JsonResponse
    {
        $evt = $this->evenementService->getById($id);
        if (!$evt) {
            return $this->json(['error' => 'Événement introuvable'], 404);
        }

        $form = $this->createForm(ParticipationType::class);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            return $this->json(['error' => 'Données invalides.'], 400);
        }

        $data        = $form->getData();
        $user        = $this->getUser();
        $nbAccomp    = $data['nb_accompagnants'] ?? 0;
        $commentaire = $data['commentaire'] ?? null;
        $prenoms     = $data['accomp_prenom'] ?? [];
        $noms        = $data['accomp_nom'] ?? [];

        $accompagnants = [];
        for ($i = 0; $i < $nbAccomp; $i++) {
            $prenom = trim($prenoms[$i] ?? '');
            $nom    = trim($noms[$i] ?? '');
            if ($prenom === '' || $nom === '') {
                return $this->json(['error' => 'Veuillez renseigner le nom et prénom de chaque accompagnant.'], 400);
            }
            $acc = new Accompagnant();
            $acc->setPrenom($prenom);
            $acc->setNom($nom);
            $accompagnants[] = $acc;
        }

        try {
            $participation = $this->participationService->inscrire($evt, $user, $nbAccomp, $accompagnants, $commentaire);
            return $this->json([
                'success' => true,
                'message' => 'Un email de confirmation a été envoyé à votre adresse. Veuillez vérifier votre boîte mail pour confirmer votre participation.',
            ]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    // ──────────────────────────────────────────
    //  POST — Annuler une participation
    // ──────────────────────────────────────────
    #[Route('/participation/{id}/annuler', name: 'user_participation_annuler', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function annulerParticipation(int $id): JsonResponse
    {
        $participation = $this->participationService->getById($id);
        if (!$participation) {
            return $this->json(['error' => 'Participation introuvable'], 404);
        }

        $user = $this->getUser();
        if ($participation->getUtilisateur()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $this->participationService->annuler($participation);
        return $this->json(['success' => true, 'message' => 'Participation annulée.']);
    }

    // ──────────────────────────────────────────
    //  POST — Modifier une participation
    // ──────────────────────────────────────────
    #[Route('/participation/{id}/modifier', name: 'user_participation_modifier', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function modifierParticipation(int $id, Request $request): JsonResponse
    {
        $participation = $this->participationService->getById($id);
        if (!$participation) {
            return $this->json(['error' => 'Participation introuvable'], 404);
        }

        $user = $this->getUser();
        if ($participation->getUtilisateur()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Non autorisé'], 403);
        }

        $form = $this->createForm(ParticipationType::class);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            return $this->json(['error' => 'Données invalides.'], 400);
        }

        $data        = $form->getData();
        $nbAccomp    = $data['nb_accompagnants'] ?? 0;
        $commentaire = $data['commentaire'] ?? null;
        $prenoms     = $data['accomp_prenom'] ?? [];
        $noms        = $data['accomp_nom'] ?? [];

        $accompagnants = [];
        for ($i = 0; $i < $nbAccomp; $i++) {
            $prenom = trim($prenoms[$i] ?? '');
            $nom    = trim($noms[$i] ?? '');
            if ($prenom === '' || $nom === '') {
                return $this->json(['error' => 'Veuillez renseigner le nom et prénom de chaque accompagnant.'], 400);
            }
            $acc = new Accompagnant();
            $acc->setPrenom($prenom);
            $acc->setNom($nom);
            $accompagnants[] = $acc;
        }

        try {
            $this->participationService->update($participation, $nbAccomp, $accompagnants, $commentaire);
            return $this->json(['success' => true, 'message' => 'Participation modifiée avec succès.']);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    // ──────────────────────────────────────────
    //  JSON — Mes participations (for modal)
    // ──────────────────────────────────────────
    #[Route('/mes-participations', name: 'user_mes_participations', methods: ['GET'])]
    public function mesParticipations(): JsonResponse
    {
        $user = $this->getUser();
        $participations = $this->participationService->getByUser($user->getId());

        $rows = [];
        foreach ($participations as $p) {
            $evt = $p->getEvenement();
            $accompList = [];
            foreach ($p->getAccompagnants() as $acc) {
                $accompList[] = [
                    'nom' => $acc->getNom(),
                    'prenom' => $acc->getPrenom(),
                    'code' => $acc->getCodeAccompagnant(),
                ];
            }
            $rows[] = [
                'participationId' => $p->getIdParticipation(),
                'code'            => $p->getStatut() === 'confirme' ? $p->getCodeParticipation() : null,
                'statut'          => $p->getStatut(),
                'dateInscription' => $p->getDateInscription()?->format('d/m/Y H:i'),
                'accompagnants'   => $p->getNombreAccompagnants(),
                'accompagnantsList' => $accompList,
                'commentaire'     => $p->getCommentaire(),
                'userName'        => $user->getPrenom() . ' ' . $user->getNom(),
                'evenement'       => [
                    'id'        => $evt->getIdEvenement(),
                    'titre'     => $evt->getTitre(),
                    'dateDebut' => $evt->getDateDebut()?->format('d/m/Y'),
                    'dateFin'   => $evt->getDateFin()?->format('d/m/Y'),
                    'horaireDebut' => $evt->getHoraireDebut()?->format('H:i'),
                    'horaireFin'   => $evt->getHoraireFin()?->format('H:i'),
                    'lieu'      => $evt->getLieu(),
                    'adresse'   => $evt->getAdresse(),
                    'organisateur' => $evt->getOrganisateur(),
                    'imageUrl'  => $evt->getImageUrl() ? $this->packages->getUrl($evt->getImageUrl()) : null,
                ],
            ];
        }

        return $this->json(['participations' => $rows]);
    }

    /** Détail d'un événement. */
    #[Route('/{id}', name: 'user_evenement_detail', requirements: ['id' => '\d+'])]
    public function detail(int $id): Response
    {
        $evenement = $this->evenementService->getById($id);
        if (!$evenement) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $sponsors = $this->sponsorRepo->findByEvenement($id);
        $user = $this->getUser();
        $participation = $user
            ? $this->participationRepo->findByUserAndEvent($user->getId(), $id)
            : null;

        $confirmedCount = $this->participationRepo->countConfirmedByEvent($id);

        return $this->render('user/event/detail.html.twig', [
            'evenement'      => $evenement,
            'sponsors'       => $sponsors,
            'participation'  => $participation,
            'confirmedCount' => $confirmedCount,
        ]);
    }
}
