<?php

namespace App\Controller\Admin\Maladie;

use App\Entity\Maladie\Maladie;
use App\Entity\Maladie\SolutionTraitement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/solution-traitement')]
#[IsGranted('ROLE_ADMIN')]
class SolutionTraitementController extends AbstractController
{
    private const ALLOWED_DURATIONS = [
        '4 jours',
        '7 jours',
        '10 jours',
        '14 jours (2 semaines)',
        '21 jours (3 semaines)',
        '30 jours (1 mois)',
    ];

    #[Route('/new/{maladie_id}', name: 'admin_solution_traitement_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, int $maladie_id): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $maladie = $em->getRepository(Maladie::class)->find($maladie_id);
        if (!$maladie) {
            return $this->json(['success' => false, 'error' => 'Maladie non trouvee']);
        }

        $titre = trim((string) ($data['titre'] ?? ''));
        $solution = trim((string) ($data['solution'] ?? ''));
        $dureeTraitement = trim((string) ($data['dureeTraitement'] ?? ''));

        if ($titre === '' || $solution === '') {
            return $this->json(['success' => false, 'error' => 'Le titre et la solution sont obligatoires']);
        }

        if ($dureeTraitement === '') {
            return $this->json(['success' => false, 'error' => 'Veuillez choisir une duree de traitement']);
        }

        if (!in_array($dureeTraitement, self::ALLOWED_DURATIONS, true)) {
            return $this->json(['success' => false, 'error' => 'Duree de traitement invalide']);
        }

        $traitement = new SolutionTraitement();
        $traitement->setMaladie($maladie);
        $traitement->setTitre(htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'));
        $traitement->setSolution(htmlspecialchars($solution, ENT_QUOTES, 'UTF-8'));
        $traitement->setEtapes($this->sanitizeNullable($data['etapes'] ?? null));
        $traitement->setProduitsRecommandes($this->sanitizeNullable($data['produitsRecommandes'] ?? null));
        $traitement->setConseilsPrevention($this->sanitizeNullable($data['conseilsPrevention'] ?? null));
        $traitement->setDureeTraitement(htmlspecialchars($dureeTraitement, ENT_QUOTES, 'UTF-8'));
        $traitement->setUsageCount(0);
        $traitement->setFeedbackPositive(0);
        $traitement->setFeedbackNegative(0);
        $traitement->setCreatedBy($this->getUser()->getId());

        $em->persist($traitement);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Traitement ajoute',
            'id' => $traitement->getId(),
        ]);
    }

    #[Route('/edit/{id}', name: 'admin_solution_traitement_edit', methods: ['POST'])]
    public function edit(Request $request, SolutionTraitement $traitement, EntityManagerInterface $em): JsonResponse
    {
        if (!$traitement) {
            return $this->json(['success' => false, 'error' => 'Traitement non trouve']);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $titre = trim((string) ($data['titre'] ?? ''));
        $solution = trim((string) ($data['solution'] ?? ''));
        $dureeTraitement = trim((string) ($data['dureeTraitement'] ?? ''));

        if ($titre === '' || $solution === '') {
            return $this->json(['success' => false, 'error' => 'Le titre et la solution sont obligatoires']);
        }

        if ($dureeTraitement === '') {
            return $this->json(['success' => false, 'error' => 'Veuillez choisir une duree de traitement']);
        }

        if (!in_array($dureeTraitement, self::ALLOWED_DURATIONS, true)) {
            return $this->json(['success' => false, 'error' => 'Duree de traitement invalide']);
        }

        $traitement->setTitre(htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'));
        $traitement->setSolution(htmlspecialchars($solution, ENT_QUOTES, 'UTF-8'));
        $traitement->setEtapes($this->sanitizeNullable($data['etapes'] ?? null));
        $traitement->setProduitsRecommandes($this->sanitizeNullable($data['produitsRecommandes'] ?? null));
        $traitement->setConseilsPrevention($this->sanitizeNullable($data['conseilsPrevention'] ?? null));
        $traitement->setDureeTraitement(htmlspecialchars($dureeTraitement, ENT_QUOTES, 'UTF-8'));

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Traitement modifie',
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_solution_traitement_delete', methods: ['POST'])]
    public function delete(Request $request, SolutionTraitement $traitement, EntityManagerInterface $em): Response
    {
        if (!$traitement) {
            $this->addFlash('error', 'Traitement non trouve');
            return $this->redirectToRoute('admin_maladie_index');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-solution' . $traitement->getId(), $token)) {
            $this->addFlash('error', 'Token invalide');
            return $this->redirectToRoute('admin_maladie_show', ['id' => $traitement->getMaladie()->getId()]);
        }

        $maladieId = $traitement->getMaladie()->getId();
        $em->remove($traitement);
        $em->flush();

        $this->addFlash('success', 'Traitement supprime avec succes');
        return $this->redirectToRoute('admin_maladie_show', ['id' => $maladieId]);
    }

    private function sanitizeNullable(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    }
}
