<?php

namespace App\Controller\Admin\Event;

use App\Entity\Event\SecteurActivite;
use App\Entity\Event\Sponsor;
use App\Form\Event\SponsorType;
use App\Service\Event\SponsorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/sponsors')]
#[IsGranted('ROLE_ADMIN')]
class SponsorController extends AbstractController
{
    public function __construct(
        private readonly SponsorService $sponsorService,
    ) {}

    // ──────────────────────────────────────────
    //  INDEX (tabs: liste, créer, modifier)
    // ──────────────────────────────────────────
    #[Route('', name: 'admin_sponsors')]
    public function index(Request $request): Response
    {
        $tab    = $request->query->get('tab', 'liste');
        $search = $request->query->get('q', '');

        $sponsors = $this->sponsorService->getCatalog();

        // Search filter
        if ($search) {
            $q = mb_strtolower($search);
            $sponsors = array_filter($sponsors, function (Sponsor $s) use ($q) {
                return str_contains(mb_strtolower($s->getNom()), $q)
                    || str_contains(mb_strtolower($s->getEmailContact() ?? ''), $q)
                    || str_contains(mb_strtolower($s->getSecteurActivite()), $q);
            });
        }

        // If editing, load the sponsor
        $editId      = $request->query->getInt('edit', 0);
        $editSponsor = $editId ? $this->sponsorService->getById($editId) : null;
        if ($editSponsor) {
            $tab = 'modifier';
        }

        return $this->render('admin/event/sponsors.html.twig', [
            'sponsors'       => $sponsors,
            'search'         => $search,
            'tab'            => $tab,
            'secteurOptions' => SecteurActivite::cases(),
            'editSponsor'    => $editSponsor,
        ]);
    }

    // ──────────────────────────────────────────
    //  JSON detail (for popup)
    // ──────────────────────────────────────────
    #[Route('/{id}/json', name: 'admin_sponsor_json', requirements: ['id' => '\d+'])]
    public function detailJson(int $id): JsonResponse
    {
        $s = $this->sponsorService->getById($id);
        if (!$s) {
            return $this->json(['error' => 'Not found'], 404);
        }

        return $this->json([
            'id'          => $s->getIdSponsor(),
            'nom'         => $s->getNom(),
            'logoUrl'     => $s->getLogoUrl(),
            'siteWeb'     => $s->getSiteWeb(),
            'emailContact'=> $s->getEmailContact(),
            'telephone'   => $s->getTelephone(),
            'description' => $s->getDescription(),
            'montant'     => $s->getMontantContribution(),
            'secteur'     => $s->getSecteurEnum()?->label() ?? $s->getSecteurActivite(),
            'dateCreation'=> $s->getDateCreation()?->format('d/m/Y'),
        ]);
    }

    // ──────────────────────────────────────────
    //  CREATE
    // ──────────────────────────────────────────
    #[Route('/create', name: 'admin_sponsor_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $sponsor = new Sponsor();
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            $this->addFlash('danger', 'Données invalides.');
            return $this->redirectToRoute('admin_evenements', ['tab' => 'sponsors']);
        }

        $this->sponsorService->addToCatalog($sponsor);
        $this->addFlash('success', 'Sponsor ajouté au catalogue.');

        return $this->redirectToRoute('admin_evenements', ['tab' => 'sponsors']);
    }

    // ──────────────────────────────────────────
    //  UPDATE
    // ──────────────────────────────────────────
    #[Route('/{id}/update', name: 'admin_sponsor_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        $sponsor = $this->sponsorService->getById($id);
        if (!$sponsor) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            $this->addFlash('danger', 'Données invalides.');
            return $this->redirectToRoute('admin_evenements', ['tab' => 'sponsors']);
        }

        $this->sponsorService->update($sponsor);

        $this->addFlash('success', 'Sponsor modifié avec succès.');
        return $this->redirectToRoute('admin_evenements', ['tab' => 'sponsors']);
    }

    // ──────────────────────────────────────────
    //  DELETE
    // ──────────────────────────────────────────
    #[Route('/{id}/delete', name: 'admin_sponsor_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $sponsor = $this->sponsorService->getById($id);
        if (!$sponsor) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_evenements', ['tab' => 'sponsors']);
        }

        $this->sponsorService->delete($sponsor);
        $this->addFlash('success', 'Sponsor supprimé.');

        return $this->redirectToRoute('admin_evenements', ['tab' => 'sponsors']);
    }

    // ──────────────────────────────────────────
    //  UPLOAD LOGO
    // ──────────────────────────────────────────
    #[Route('/upload-logo', name: 'admin_sponsor_upload_logo', methods: ['POST'])]
    public function uploadLogo(Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'Aucun fichier valide reçu.'], 400);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return $this->json(['error' => 'Format non supporté.'], 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['error' => 'Fichier trop volumineux (max 5 Mo).'], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/sponsors';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = $file->guessExtension() ?: 'png';
        $filename = 'sp_' . uniqid() . '.' . $ext;
        $file->move($uploadDir, $filename);

        return $this->json(['url' => '/uploads/sponsors/' . $filename]);
    }

}
