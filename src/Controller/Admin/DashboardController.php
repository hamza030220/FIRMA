<?php

namespace App\Controller\Admin;

use App\Repository\Marketplace\CommandeRepository;
use App\Repository\Marketplace\EquipementRepository;
use App\Repository\Marketplace\FournisseurRepository;
use App\Repository\Marketplace\LocationRepository;
use App\Repository\Marketplace\TerrainRepository;
use App\Repository\Marketplace\VehiculeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(
        EquipementRepository $equipRepo,
        VehiculeRepository $vehicRepo,
        TerrainRepository $terrainRepo,
        FournisseurRepository $fournRepo,
        CommandeRepository $cmdRepo,
        LocationRepository $locRepo,
    ): Response {
        $totalProducts = $equipRepo->count([]) + $vehicRepo->count([]) + $terrainRepo->count([]);
        $pendingOrders = $cmdRepo->count(['statutPaiement' => 'en_attente']);
        $activeLocations = $locRepo->count(['statut' => 'en_cours']);
        $totalFournisseurs = $fournRepo->count(['actif' => true]);

        return $this->render('admin/dashboard.html.twig', [
            'totalProducts' => $totalProducts,
            'pendingOrders' => $pendingOrders,
            'activeLocations' => $activeLocations,
            'totalFournisseurs' => $totalFournisseurs,
            'totalCommandes' => $cmdRepo->count([]),
            'totalLocations' => $locRepo->count([]),
        ]);
    }

    #[Route('/evenements', name: 'admin_evenements')]
    public function evenements(): Response
    {
        return $this->render('admin/event/index.html.twig');
    }

    #[Route('/techniciens', name: 'admin_techniciens')]
    public function techniciens(): Response
    {
        return $this->render('admin/tech/index.html.twig');
    }

    #[Route('/forum', name: 'admin_forum')]
    public function forum(): Response
    {
        return $this->render('admin/forum/index.html.twig');
    }

    #[Route('/utilisateurs', name: 'admin_utilisateurs')]
    public function utilisateurs(): Response
    {
        return $this->render('admin/user/index.html.twig');
    }
}
