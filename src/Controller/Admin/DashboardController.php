<?php

namespace App\Controller\Admin;

use App\Repository\Event\EvenementRepository;
use App\Repository\Event\ParticipationRepository;
use App\Repository\Event\SponsorRepository;
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
        EvenementRepository $evtRepo,
        ParticipationRepository $partRepo,
        SponsorRepository $sponsorRepo,
    ): Response {
        $totalProducts = $equipRepo->count([]) + $vehicRepo->count([]) + $terrainRepo->count([]);
        $pendingOrders = $cmdRepo->count(['statutPaiement' => 'en_attente']);
        $activeLocations = $locRepo->count(['statut' => 'en_cours']);
        $totalFournisseurs = $fournRepo->count(['actif' => true]);

        // Event stats
        $totalEvenements   = $evtRepo->countAll();
        $evenementsActifs   = $evtRepo->countActifs();
        $tauxRemplissage    = $evtRepo->tauxRemplissageMoyen();
        $totalParticipants  = $partRepo->countTotalParticipants();
        $confirmees         = $partRepo->countConfirmees();
        $enAttente          = $partRepo->countEnAttente();
        $totalSponsors      = $sponsorRepo->count([]);
        $totalContributions = $sponsorRepo->totalContributions();
        $evtCetteSemaine    = $evtRepo->countCetteSemaine();
        $evtCeMois          = $evtRepo->countCeMois();

        return $this->render('admin/dashboard.html.twig', [
            'totalProducts' => $totalProducts,
            'pendingOrders' => $pendingOrders,
            'activeLocations' => $activeLocations,
            'totalFournisseurs' => $totalFournisseurs,
            'totalCommandes' => $cmdRepo->count([]),
            'totalLocations' => $locRepo->count([]),
            'totalTerrains' => $terrainRepo->count([]),
            // Events
            'totalEvenements'   => $totalEvenements,
            'evenementsActifs'  => $evenementsActifs,
            'tauxRemplissage'   => $tauxRemplissage,
            'totalParticipants' => $totalParticipants,
            'confirmees'        => $confirmees,
            'enAttente'         => $enAttente,
            'totalSponsors'     => $totalSponsors,
            'totalContributions'=> $totalContributions,
            'evtCetteSemaine'   => $evtCetteSemaine,
            'evtCeMois'         => $evtCeMois,
        ]);
    }

    #[Route('/marketplace', name: 'admin_marketplace')]
    public function marketplace(): Response
    {
        return $this->render('admin/marketplace/index.html.twig');
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
