<?php
// src/Controller/Admin/SolutionTraitementController.php

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
    // ==================== AJOUTER (AJAX) ====================
    #[Route('/new/{maladie_id}', name: 'admin_solution_traitement_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $em, int $maladie_id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $maladie = $em->getRepository(Maladie::class)->find($maladie_id);
        if (!$maladie) {
            return $this->json(['success' => false, 'error' => 'Maladie non trouvée']);
        }
        
        if (empty($data['titre']) || empty($data['solution'])) {
            return $this->json(['success' => false, 'error' => 'Le titre et la solution sont obligatoires']);
        }
        
        $traitement = new SolutionTraitement();
        $traitement->setMaladie($maladie);
        $traitement->setTitre(htmlspecialchars(trim($data['titre']), ENT_QUOTES, 'UTF-8'));
        $traitement->setSolution(htmlspecialchars(trim($data['solution']), ENT_QUOTES, 'UTF-8'));
        $traitement->setEtapes(!empty($data['etapes']) ? htmlspecialchars(trim($data['etapes']), ENT_QUOTES, 'UTF-8') : null);
        $traitement->setProduitsRecommandes(!empty($data['produitsRecommandes']) ? htmlspecialchars(trim($data['produitsRecommandes']), ENT_QUOTES, 'UTF-8') : null);
        $traitement->setConseilsPrevention(!empty($data['conseilsPrevention']) ? htmlspecialchars(trim($data['conseilsPrevention']), ENT_QUOTES, 'UTF-8') : null);
        $traitement->setDureeTraitement(!empty($data['dureeTraitement']) ? htmlspecialchars(trim($data['dureeTraitement']), ENT_QUOTES, 'UTF-8') : null);
        $traitement->setUsageCount(0);
        $traitement->setFeedbackPositive(0);
        $traitement->setFeedbackNegative(0);
        $traitement->setCreatedBy($this->getUser()->getId());
        
        $em->persist($traitement);
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Traitement ajouté',
            'id' => $traitement->getId()
        ]);
    }

    // ==================== MODIFIER (AJAX) ====================
    #[Route('/edit/{id}', name: 'admin_solution_traitement_edit', methods: ['POST'])]
    public function edit(Request $request, SolutionTraitement $traitement, EntityManagerInterface $em): JsonResponse
    {
        if (!$traitement) {
            return $this->json(['success' => false, 'error' => 'Traitement non trouvé']);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['titre']) || empty($data['solution'])) {
            return $this->json(['success' => false, 'error' => 'Le titre et la solution sont obligatoires']);
        }
        
        $traitement->setTitre(htmlspecialchars(trim($data['titre']), ENT_QUOTES, 'UTF-8'));
        $traitement->setSolution(htmlspecialchars(trim($data['solution']), ENT_QUOTES, 'UTF-8'));
        $traitement->setEtapes(!empty($data['etapes']) ? htmlspecialchars(trim($data['etapes']), ENT_QUOTES, 'UTF-8') : null);
        $traitement->setProduitsRecommandes(!empty($data['produitsRecommandes']) ? htmlspecialchars(trim($data['produitsRecommandes']), ENT_QUOTES, 'UTF-8') : null);
        $traitement->setConseilsPrevention(!empty($data['conseilsPrevention']) ? htmlspecialchars(trim($data['conseilsPrevention']), ENT_QUOTES, 'UTF-8') : null);
        $traitement->setDureeTraitement(!empty($data['dureeTraitement']) ? htmlspecialchars(trim($data['dureeTraitement']), ENT_QUOTES, 'UTF-8') : null);
        
        $em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Traitement modifié'
        ]);
    }

    // ==================== SUPPRIMER ====================
    #[Route('/delete/{id}', name: 'admin_solution_traitement_delete', methods: ['POST'])]
    public function delete(Request $request, SolutionTraitement $traitement, EntityManagerInterface $em): Response
    {
        if (!$traitement) {
            $this->addFlash('error', 'Traitement non trouvé');
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
        
        $this->addFlash('success', 'Traitement supprimé avec succès');
        return $this->redirectToRoute('admin_maladie_show', ['id' => $maladieId]);
    }
}
