<?php

namespace App\Controller\Admin\Event;

use App\Entity\Event\Evenement;
use App\Entity\Event\SecteurActivite;
use App\Entity\Event\TypeEvenement;
use App\Form\Event\EvenementType;
use App\Service\Event\EvenementService;
use App\Service\Event\ParticipationService;
use App\Service\Event\SponsorService;
use App\Repository\Event\EvenementRepository;
use App\Repository\Event\ParticipationRepository;
use App\Repository\Event\SponsorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Asset\Packages;

#[Route('/admin/evenements')]
#[IsGranted('ROLE_ADMIN')]
class EvenementController extends AbstractController
{
    public function __construct(
        private readonly EvenementService $evenementService,
        private readonly ParticipationService $participationService,
        private readonly SponsorService $sponsorService,
        private readonly EvenementRepository $evenementRepo,
        private readonly ParticipationRepository $participationRepo,
        private readonly SponsorRepository $sponsorRepo,
        private readonly Packages $packages,
    ) {}

    /**
     * Build all common template variables for the index page.
     */
    private function getIndexTemplateData(Request $request, ?string $overrideTab = null): array
    {
        $tab    = $overrideTab ?? $request->query->get('tab', 'liste');
        $search = $request->query->get('q', '');
        $sort   = $request->query->get('sort', 'date_desc');

        $evenements = $search
            ? $this->evenementService->search($search)
            : $this->evenementService->getAll();

        usort($evenements, match ($sort) {
            'date_desc'  => fn($a, $b) => $b->getDateDebut() <=> $a->getDateDebut(),
            'titre_asc'  => fn($a, $b) => strcasecmp($a->getTitre(), $b->getTitre()),
            'titre_desc' => fn($a, $b) => strcasecmp($b->getTitre(), $a->getTitre()),
            'places'     => fn($a, $b) => $b->getPlacesDisponibles() <=> $a->getPlacesDisponibles(),
            'statut'     => fn($a, $b) => strcmp($a->getStatut(), $b->getStatut()),
            default      => fn($a, $b) => $a->getDateDebut() <=> $b->getDateDebut(),
        });

        $participationCounts = [];
        foreach ($evenements as $evt) {
            $participationCounts[$evt->getIdEvenement()] =
                $this->participationRepo->countConfirmedByEvent($evt->getIdEvenement());
        }

        $catalogSponsors = $this->sponsorService->getCatalog();

        $editId    = $request->query->getInt('edit', 0);
        $editEvent = $editId ? $this->evenementService->getById($editId) : null;
        $editSponsorIds = [];
        if ($editEvent) {
            $tab = 'modifier';
            foreach ($this->sponsorRepo->findByEvenement($editId) as $s) {
                foreach ($catalogSponsors as $cs) {
                    if ($cs->getNom() === $s->getNom()) {
                        $editSponsorIds[] = $cs->getIdSponsor();
                        break;
                    }
                }
            }
        }

        $dashStats = [
            'totalEvents'       => $this->evenementRepo->countAll(),
            'eventsActifs'      => $this->evenementRepo->countActifs(),
            'eventsCetteSemaine'=> $this->evenementRepo->countCetteSemaine(),
            'eventsCeMois'      => $this->evenementRepo->countCeMois(),
            'tauxRemplissage'   => $this->evenementRepo->tauxRemplissageMoyen(),
            'totalParticipants' => $this->participationRepo->countTotalParticipants(),
            'participConfirm'   => $this->participationRepo->countConfirmees(),
            'participAttente'   => $this->participationRepo->countEnAttente(),
            'totalSponsors'     => count($catalogSponsors),
            'sponsorsAssignes'  => $this->sponsorRepo->countAssignes(),
            'totalContributions'=> $this->sponsorRepo->totalContributions(),
        ];

        return [
            'evenements'          => $evenements,
            'search'              => $search,
            'sort'                => $sort,
            'tab'                 => $tab,
            'participationCounts' => $participationCounts,
            'catalogSponsors'     => $catalogSponsors,
            'typeOptions'         => TypeEvenement::cases(),
            'secteurOptions'      => SecteurActivite::cases(),
            'editEvent'           => $editEvent,
            'editSponsorIds'      => $editSponsorIds,
            'dashStats'           => $dashStats,
            'formErrors'          => [],
            'formData'            => [],
            'editFormErrors'      => [],
        ];
    }

    /**
     * Extract per-field errors from a submitted Symfony form.
     */
    private function extractFormErrors(\Symfony\Component\Form\FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $field = $error->getOrigin()?->getName() ?? 'global';
            $errors[$field][] = $error->getMessage();
        }
        return $errors;
    }

    // ──────────────────────────────────────────
    //  LIST (tab = liste, default)
    // ──────────────────────────────────────────
    #[Route('', name: 'admin_evenements')]
    public function index(Request $request): Response
    {
        return $this->render('admin/event/index.html.twig', $this->getIndexTemplateData($request));
    }

    // ──────────────────────────────────────────
    //  GENERATE IMAGE via Hugging Face
    // ──────────────────────────────────────────
    #[Route('/generate-image', name: 'admin_evenement_generate_image', methods: ['POST'])]
    public function generateImage(Request $request): JsonResponse
    {
        $prompt = $request->request->get('prompt', 'agricultural event');
        $token  = $this->getParameter('app.huggingface_token');

        // Call Hugging Face Inference API (FLUX.1-schnell)
        $model = 'black-forest-labs/FLUX.1-schnell';
        $url   = 'https://router.huggingface.co/hf-inference/models/' . $model;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'inputs' => $prompt,
            ]),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            // Check if it's a JSON error from HF
            $decoded = json_decode($response, true);
            $msg = $decoded['error'] ?? ($error ?: 'Erreur Hugging Face (HTTP ' . $httpCode . ')');
            return $this->json(['error' => $msg], 500);
        }

        // If the response is JSON instead of binary, it's an error/loading message
        if (str_starts_with(trim($response), '{')) {
            $decoded = json_decode($response, true);
            return $this->json(['error' => $decoded['error'] ?? 'Modèle en cours de chargement, réessayez dans quelques secondes.'], 503);
        }

        // Save the image
        $uploadDir = $this->getParameter('kernel.project_dir') . '/assets/image/event';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'ai_' . uniqid() . '.png';
        file_put_contents($uploadDir . '/' . $filename, $response);

        return $this->json([
            'url' => 'image/event/' . $filename,
        ]);
    }

    // ──────────────────────────────────────────
    //  UPLOAD IMAGE (from local file)
    // ──────────────────────────────────────────
    #[Route('/upload-image', name: 'admin_evenement_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
        $file = $request->files->get('image');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'Aucun fichier valide reçu.'], 400);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return $this->json(['error' => 'Format non supporté. Utilisez JPG, PNG, GIF ou WebP.'], 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['error' => 'Fichier trop volumineux (max 5 Mo).'], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/assets/image/event';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = $file->guessExtension() ?: 'jpg';
        $filename = 'evt_' . uniqid() . '.' . $ext;
        $file->move($uploadDir, $filename);

        return $this->json([
            'url' => 'image/event/' . $filename,
        ]);
    }

    // ──────────────────────────────────────────
    //  DETAIL JSON (for popup)
    // ──────────────────────────────────────────
    #[Route('/{id}/json', name: 'admin_evenement_json', requirements: ['id' => '\d+'])]
    public function detailJson(int $id): JsonResponse
    {
        $evt = $this->evenementService->getById($id);
        if (!$evt) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $sponsors = $this->sponsorRepo->findByEvenement($id);
        $confirmed = $this->participationRepo->countConfirmedByEvent($id);

        $sponsorData = [];
        foreach ($sponsors as $s) {
            $sponsorData[] = [
                'nom'      => $s->getNom(),
                'secteur'  => $s->getSecteurActivite(),
                'email'    => $s->getEmailContact(),
            ];
        }

        return $this->json([
            'id'            => $evt->getIdEvenement(),
            'titre'         => $evt->getTitre(),
            'description'   => $evt->getDescription(),
            'imageUrl'      => $evt->getImageUrl() ? $this->packages->getUrl($evt->getImageUrl()) : null,
            'type'          => $evt->getTypeEnum()?->label() ?? $evt->getTypeEvenement(),
            'statut'        => $evt->getStatutEnum()?->label() ?? $evt->getStatut(),
            'statutClass'   => $evt->getStatutEnum()?->badgeClass() ?? 'badge-secondary',
            'organisateur'  => $evt->getOrganisateur(),
            'dateDebut'     => $evt->getDateDebut()?->format('d/m/Y'),
            'dateFin'       => $evt->getDateFin()?->format('d/m/Y'),
            'dateDebutRaw'  => $evt->getDateDebut()?->format('Y-m-d'),
            'horaireDebut'  => $evt->getHoraireDebut()?->format('H:i'),
            'horaireFin'    => $evt->getHoraireFin()?->format('H:i'),
            'lieu'          => $evt->getLieu(),
            'adresse'       => $evt->getAdresse(),
            'capaciteMax'   => $evt->getCapaciteMax(),
            'placesDisponibles' => $evt->getPlacesDisponibles(),
            'participations'    => $confirmed,
            'googleMapsUrl'     => $evt->getGoogleMapsUrl(),
            'sponsors'          => $sponsorData,
        ]);
    }

    // ──────────────────────────────────────────
    //  DETAIL page (fallback)
    // ──────────────────────────────────────────
    #[Route('/{id}', name: 'admin_evenement_detail', requirements: ['id' => '\d+'])]
    public function detail(int $id): Response
    {
        $evenement = $this->evenementService->getById($id);
        if (!$evenement) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $sponsors       = $this->sponsorRepo->findByEvenement($id);
        $confirmedCount = $this->participationRepo->countConfirmedByEvent($id);
        $totalPersonnes = $this->participationRepo->countTotalPersonnesByEvent($id);

        return $this->render('admin/event/detail.html.twig', [
            'evenement'      => $evenement,
            'sponsors'       => $sponsors,
            'confirmedCount' => $confirmedCount,
            'totalPersonnes' => $totalPersonnes,
        ]);
    }

    // ──────────────────────────────────────────
    //  CALENDAR EVENTS JSON
    // ──────────────────────────────────────────
    #[Route('/calendar-events', name: 'admin_calendar_events', methods: ['GET'])]
    public function calendarEvents(): JsonResponse
    {
        $allEvents = $this->evenementService->getAll();
        $events = [];
        foreach ($allEvents as $evt) {
            $start = $evt->getDateDebut();
            $end   = $evt->getDateFin() ?? $start;
            if ($evt->getHoraireDebut()) {
                $start = new \DateTime($start->format('Y-m-d') . ' ' . $evt->getHoraireDebut()->format('H:i'));
            }
            if ($evt->getHoraireFin()) {
                $end = new \DateTime($end->format('Y-m-d') . ' ' . $evt->getHoraireFin()->format('H:i'));
            } else {
                $end = (clone $end)->modify('+1 day');
            }
            $events[] = [
                'id'            => $evt->getIdEvenement(),
                'title'         => $evt->getTitre(),
                'start'         => $start->format('c'),
                'end'           => $end->format('c'),
                'extendedProps' => [
                    'lieu'         => $evt->getLieu(),
                    'adresse'      => $evt->getAdresse(),
                    'organisateur' => $evt->getOrganisateur(),
                    'imageUrl'     => $evt->getImageUrl() ? $this->packages->getUrl($evt->getImageUrl()) : null,
                ],
            ];
        }
        return $this->json($events);
    }

    // ──────────────────────────────────────────
    //  CREATE (POST)
    // ──────────────────────────────────────────
    #[Route('/create', name: 'admin_evenement_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $evt  = new Evenement();
        $form = $this->createForm(EvenementType::class, $evt);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            $fieldErrors = $this->extractFormErrors($form);
            $errors = [];
            $errorFields = [];
            foreach ($fieldErrors as $field => $msgs) {
                if ($field !== 'global') {
                    $errorFields[] = $field;
                }
                foreach ($msgs as $msg) {
                    $errors[] = $msg;
                }
            }
            return $this->json(['success' => false, 'errors' => $errors, 'errorFields' => $errorFields]);
        }

        $this->evenementService->create($evt);

        // Sponsors
        $sponsorIds = array_map('intval', $request->request->all('sponsors') ?: []);
        if ($sponsorIds) {
            $this->sponsorService->assignerMultiple($sponsorIds, $evt);
        }

        return $this->json(['success' => true, 'message' => 'Événement créé avec succès.']);
    }

    // ──────────────────────────────────────────
    //  UPDATE (POST)
    // ──────────────────────────────────────────
    #[Route('/{id}/update', name: 'admin_evenement_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $evt = $this->evenementService->getById($id);
        if (!$evt) {
            return $this->json(['success' => false, 'errors' => ['Événement introuvable.']], 404);
        }

        $oldCapacite = $evt->getCapaciteMax();

        $form = $this->createForm(EvenementType::class, $evt);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            $fieldErrors = $this->extractFormErrors($form);
            $errors = [];
            $errorFields = [];
            foreach ($fieldErrors as $field => $msgs) {
                if ($field !== 'global') {
                    $errorFields[] = $field;
                }
                foreach ($msgs as $msg) {
                    $errors[] = $msg;
                }
            }
            return $this->json(['success' => false, 'errors' => $errors, 'errorFields' => $errorFields]);
        }

        // Adjust available places if capacity changed
        $diff = $evt->getCapaciteMax() - $oldCapacite;
        if ($diff !== 0) {
            $evt->setPlacesDisponibles(max(0, $evt->getPlacesDisponibles() + $diff));
        }

        $this->evenementService->update($evt);

        // Sync sponsors
        $sponsorIds = array_map('intval', $request->request->all('sponsors') ?: []);
        $this->sponsorService->syncForEvent($sponsorIds, $evt);

        // Notify participants about the modification
        try {
            $this->participationService->notifyEventModified($evt);
        } catch (\Throwable $e) {
            // Email failure should not block the update
        }

        return $this->json(['success' => true, 'message' => 'Événement modifié avec succès.']);
    }

    // ──────────────────────────────────────────
    //  CANCEL (POST)
    // ──────────────────────────────────────────
    #[Route('/{id}/cancel', name: 'admin_evenement_cancel', methods: ['POST'])]
    public function cancel(int $id): Response
    {
        $evenement = $this->evenementService->getById($id);
        if (!$evenement) {
            throw $this->createNotFoundException();
        }

        // Notify participants before cancelling
        try {
            $this->participationService->notifyEventCancelled($evenement);
        } catch (\Throwable $e) {
            // Email failure should not block the cancellation
        }

        // Cancel all active participations
        $this->participationService->cancelAllForEvent($evenement->getIdEvenement());

        $this->evenementService->updateStatut($evenement, 'annule');
        $this->addFlash('success', 'Événement annulé avec succès.');

        return $this->redirectToRoute('admin_evenements');
    }

    // ──────────────────────────────────────────
    //  DELETE (POST)
    // ──────────────────────────────────────────
    #[Route('/{id}/delete', name: 'admin_evenement_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $evenement = $this->evenementService->getById($id);
        if (!$evenement) {
            throw $this->createNotFoundException();
        }

        $this->sponsorRepo->deleteByEvenement($id);
        $this->evenementService->delete($evenement);
        $this->addFlash('success', 'Événement supprimé avec succès.');

        return $this->redirectToRoute('admin_evenements');
    }

    // ──────────────────────────────────────────
    //  PARTICIPANTS (redirect old URL → index)
    // ──────────────────────────────────────────
    #[Route('/{id}/participants', name: 'admin_evenement_participants', requirements: ['id' => '\d+'])]
    public function participantsRedirect(): Response
    {
        return $this->redirectToRoute('admin_evenements');
    }

    // ──────────────────────────────────────────
    //  PARTICIPANTS (JSON for popup)
    // ──────────────────────────────────────────
    #[Route('/{id}/participants/json', name: 'admin_evenement_participants_json', requirements: ['id' => '\d+'])]
    public function participantsJson(int $id): JsonResponse
    {
        $evenement = $this->evenementService->getById($id);
        if (!$evenement) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $participations = $this->participationRepo->findParticipantsDetailsByEvent($id);
        $totalPersonnes = $this->participationRepo->countTotalPersonnesByEvent($id);

        $rows = [];
        foreach ($participations as $p) {
            $user = $p->getUtilisateur();
            $accompagnants = [];
            foreach ($p->getAccompagnants() as $a) {
                $accompagnants[] = $a->getFullName();
            }
            $rows[] = [
                'fullName'           => $user ? $user->getFullName() : '—',
                'email'              => $user ? $user->getEmail() : '—',
                'statut'             => $p->getStatut(),
                'nombreAccompagnants'=> $p->getNombreAccompagnants(),
                'accompagnants'      => $accompagnants,
                'commentaire'        => $p->getCommentaire() ?? '',
            ];
        }

        return $this->json([
            'titre'          => $evenement->getTitre(),
            'totalPersonnes' => $totalPersonnes,
            'participants'   => $rows,
        ]);
    }

    // ──────────────────────────────────────────
    //  DASHBOARD — chart data (JSON)
    // ──────────────────────────────────────────
    #[Route('/dashboard-data', name: 'admin_evenement_dashboard_data', methods: ['GET'])]
    public function dashboardData(): JsonResponse
    {
        return $this->json([
            'repartitionType'       => $this->evenementRepo->repartitionParType(),
            'repartitionStatut'     => $this->evenementRepo->repartitionParStatut(),
            'evenementsParMois'     => $this->evenementRepo->evenementsParMois(),
            'topEvenements'         => $this->evenementRepo->topEvenements(5),
            'placesDisponibles'     => $this->evenementRepo->evenementsPlacesDisponibles(5),
            'participationsParMois' => $this->participationRepo->participationsParMois(),
            'repartitionParticip'   => $this->participationRepo->repartitionParStatut(),
            'repartitionSecteur'    => $this->sponsorRepo->repartitionParSecteur(),
            'topSponsors'           => $this->sponsorRepo->topSponsors(5),
        ]);
    }

}
