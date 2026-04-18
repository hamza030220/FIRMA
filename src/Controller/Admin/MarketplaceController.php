<?php

namespace App\Controller\Admin;

use App\Entity\Marketplace\Equipement;
use App\Entity\Marketplace\Fournisseur;
use App\Entity\Marketplace\Vehicule;
use App\Entity\Marketplace\Terrain;
use App\Entity\Marketplace\Commande;
use App\Entity\Marketplace\Location;
use App\Form\Marketplace\EquipementType;
use App\Form\Marketplace\VehiculeType;
use App\Form\Marketplace\TerrainType;
use App\Form\Marketplace\FournisseurType;
use App\Repository\Marketplace\EquipementRepository;
use App\Repository\Marketplace\VehiculeRepository;
use App\Repository\Marketplace\TerrainRepository;
use App\Repository\Marketplace\FournisseurRepository;
use App\Repository\Marketplace\CommandeRepository;
use App\Repository\Marketplace\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\Marketplace\PdfMailerService;

#[Route('/admin/marketplace')]
#[IsGranted('ROLE_ADMIN')]
class MarketplaceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
    ) {}

    /* ================================================================
       DASHBOARD  (6 cards)
       ================================================================ */

    #[Route('', name: 'admin_marketplace')]
    public function index(): Response
    {
        $counts = $this->em->getConnection()->executeQuery("
            SELECT
                (SELECT COUNT(*) FROM equipements) as equipements,
                (SELECT COUNT(*) FROM vehicules) as vehicules,
                (SELECT COUNT(*) FROM terrains) as terrains,
                (SELECT COUNT(*) FROM fournisseurs) as fournisseurs,
                (SELECT COUNT(*) FROM commandes) as commandes,
                (SELECT COUNT(*) FROM locations) as locations
        ")->fetchAssociative();

        return $this->render('admin/marketplace/index.html.twig', [
            'counts' => $counts,
        ]);
    }

    /* ================================================================
       ANALYTICS  (Real-time KPIs & Stats)
       ================================================================ */

    #[Route('/analytics', name: 'admin_marketplace_analytics')]
    public function analytics(
        CommandeRepository $cmdRepo,
        LocationRepository $locRepo,
        EquipementRepository $equipRepo,
        VehiculeRepository $vehicRepo,
        TerrainRepository $terrainRepo,
    ): Response {
        $conn = $this->em->getConnection();

        // Revenue stats (last 30 days vs previous 30 days)
        $revenueCurrent = $conn->executeQuery("
            SELECT COALESCE(SUM(CAST(montant_total AS DECIMAL(10,2))), 0) as total
            FROM commandes WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetchOne();

        $revenuePrevious = $conn->executeQuery("
            SELECT COALESCE(SUM(CAST(montant_total AS DECIMAL(10,2))), 0) as total
            FROM commandes WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                             AND date_commande < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetchOne();

        // Orders per day (last 14 days)
        $ordersPerDay = $conn->executeQuery("
            SELECT DATE(date_commande) as jour, COUNT(*) as nb, SUM(CAST(montant_total AS DECIMAL(10,2))) as montant
            FROM commandes WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 14 DAY)
            GROUP BY DATE(date_commande) ORDER BY jour
        ")->fetchAllAssociative();

        // Locations per day (last 14 days)
        $locsPerDay = $conn->executeQuery("
            SELECT DATE(date_debut) as jour, COUNT(*) as nb
            FROM locations WHERE date_debut >= DATE_SUB(NOW(), INTERVAL 14 DAY)
            GROUP BY DATE(date_debut) ORDER BY jour
        ")->fetchAllAssociative();

        // Top 5 best sellers
        $topEquipements = $conn->executeQuery("
            SELECT e.nom, SUM(d.quantite) as total_vendu, SUM(CAST(d.sous_total AS DECIMAL(10,2))) as ca
            FROM details_commandes d
            JOIN equipements e ON e.id = d.equipement_id
            GROUP BY e.id, e.nom ORDER BY total_vendu DESC LIMIT 5
        ")->fetchAllAssociative();

        // Payment method distribution
        $paymentStats = $conn->executeQuery("
            SELECT statut_paiement, COUNT(*) as nb
            FROM commandes GROUP BY statut_paiement
        ")->fetchAllAssociative();

        // Location type split
        $locTypeStats = $conn->executeQuery("
            SELECT type_location, COUNT(*) as nb, SUM(CAST(prix_total AS DECIMAL(10,2))) as ca
            FROM locations GROUP BY type_location
        ")->fetchAllAssociative();

        // Low stock alerts
        $lowStockItems = $conn->executeQuery("
            SELECT nom, quantite_stock, seuil_alerte
            FROM equipements WHERE quantite_stock <= seuil_alerte AND disponible = 1
            ORDER BY quantite_stock ASC LIMIT 10
        ")->fetchAllAssociative();

        // Delivery status breakdown
        $deliveryStats = $conn->executeQuery("
            SELECT statut_livraison, COUNT(*) as nb
            FROM commandes GROUP BY statut_livraison
        ")->fetchAllAssociative();

        // Overall counts
        $totals = $conn->executeQuery("
            SELECT
                (SELECT COUNT(*) FROM commandes) as commandes,
                (SELECT COUNT(*) FROM locations) as locations,
                (SELECT COALESCE(SUM(CAST(montant_total AS DECIMAL(10,2))), 0) FROM commandes WHERE statut_paiement = 'paye') as ca_total,
                (SELECT COUNT(*) FROM commandes WHERE statut_livraison = 'en_preparation') as en_preparation
        ")->fetchAssociative();

        // Average order value
        $avgOrder = $conn->executeQuery("
            SELECT COALESCE(AVG(CAST(montant_total AS DECIMAL(10,2))), 0) as avg_val
            FROM commandes WHERE statut_paiement = 'paye'
        ")->fetchOne();

        // Revenue from locations
        $locRevenue = $conn->executeQuery("
            SELECT COALESCE(SUM(CAST(prix_total AS DECIMAL(10,2))), 0) +
                   COALESCE(SUM(CAST(caution AS DECIMAL(10,2))), 0) as total
            FROM locations WHERE statut = 'confirmee'
        ")->fetchOne();

        // Monthly revenue (last 6 months)
        $monthlyRevenue = $conn->executeQuery("
            SELECT DATE_FORMAT(date_commande, '%Y-%m') as mois,
                   COUNT(*) as nb_commandes,
                   SUM(CAST(montant_total AS DECIMAL(10,2))) as ca
            FROM commandes
            WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(date_commande, '%Y-%m')
            ORDER BY mois
        ")->fetchAllAssociative();

        // Top clients
        $topClients = $conn->executeQuery("
            SELECT u.nom, u.prenom, u.email,
                   COUNT(c.id) as nb_commandes,
                   SUM(CAST(c.montant_total AS DECIMAL(10,2))) as total_depense
            FROM commandes c
            JOIN utilisateurs u ON u.id = c.id_utilisateur
            GROUP BY u.id, u.nom, u.prenom, u.email
            ORDER BY total_depense DESC LIMIT 5
        ")->fetchAllAssociative();

        // Active locations now
        $activeLocations = $conn->executeQuery("
            SELECT COUNT(*) as nb FROM locations
            WHERE statut = 'confirmee' AND date_debut <= NOW() AND date_fin >= NOW()
        ")->fetchOne();

        // Stock value
        $stockValue = $conn->executeQuery("
            SELECT COALESCE(SUM(CAST(prix_vente AS DECIMAL(10,2)) * quantite_stock), 0) as valeur
            FROM equipements WHERE disponible = 1
        ")->fetchOne();

        // Recent orders (last 5)
        $recentOrders = $conn->executeQuery("
            SELECT c.numero_commande, c.montant_total, c.statut_paiement, c.statut_livraison,
                   c.date_commande, u.nom, u.prenom
            FROM commandes c
            JOIN utilisateurs u ON u.id = c.id_utilisateur
            ORDER BY c.date_commande DESC LIMIT 5
        ")->fetchAllAssociative();

        return $this->render('admin/marketplace/analytics.html.twig', [
            'revenueCurrent' => (float) $revenueCurrent,
            'revenuePrevious' => (float) $revenuePrevious,
            'ordersPerDay' => $ordersPerDay,
            'locsPerDay' => $locsPerDay,
            'topEquipements' => $topEquipements,
            'paymentStats' => $paymentStats,
            'locTypeStats' => $locTypeStats,
            'lowStockItems' => $lowStockItems,
            'deliveryStats' => $deliveryStats,
            'totals' => $totals,
            'avgOrder' => (float) $avgOrder,
            'locRevenue' => (float) $locRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'topClients' => $topClients,
            'activeLocations' => (int) $activeLocations,
            'stockValue' => (float) $stockValue,
            'recentOrders' => $recentOrders,
        ]);
    }

    /* ================================================================
       CALENDRIER LOCATIONS  (FullCalendar)
       ================================================================ */

    #[Route('/calendrier', name: 'admin_marketplace_calendrier')]
    public function calendrier(
        VehiculeRepository $vehicRepo,
        TerrainRepository $terrainRepo,
    ): Response {
        $vehicules = $vehicRepo->findBy([], ['nom' => 'ASC']);
        $terrains = $terrainRepo->findBy([], ['titre' => 'ASC']);

        return $this->render('admin/marketplace/calendrier.html.twig', [
            'vehicules' => $vehicules,
            'terrains' => $terrains,
        ]);
    }

    #[Route('/calendrier/events', name: 'admin_marketplace_calendrier_events', methods: ['GET'])]
    public function calendarEvents(Request $request, LocationRepository $locRepo): JsonResponse
    {
        $locations = $locRepo->findCurrentAndFutureWithRelations();

        $filterType = $request->query->get('filterType', 'all');
        $filterId = $request->query->getInt('filterId', 0);

        $events = [];
        foreach ($locations as $loc) {
            $type = $loc->getTypeLocation();

            if ($filterType !== 'all' && $filterType !== $type) {
                continue;
            }
            if ($filterId > 0) {
                if ($type === 'vehicule' && $loc->getVehicule()?->getId() !== $filterId) continue;
                if ($type === 'terrain' && $loc->getTerrain()?->getId() !== $filterId) continue;
            }

            $user = $loc->getUtilisateur();
            $itemName = $loc->getItemName();
            $userName = $user ? $user->getFullName() : 'Inconnu';

            $events[] = [
                'id' => $loc->getId(),
                'title' => $itemName,
                'start' => $loc->getDateDebut()->format('Y-m-d'),
                'end' => (clone $loc->getDateFin())->modify('+1 day')->format('Y-m-d'),
                'color' => $type === 'terrain' ? '#27ae60' : '#e67e22',
                'extendedProps' => [
                    'type' => $type,
                    'itemName' => $itemName,
                    'userName' => $userName,
                    'prixTotal' => $loc->getPrixTotal() . ' TND',
                    'statut' => $loc->getStatut(),
                    'dateDebut' => $loc->getDateDebut()->format('d/m/Y'),
                    'dateFin' => $loc->getDateFin()->format('d/m/Y'),
                ],
            ];
        }

        return new JsonResponse($events);
    }

    /* ================================================================
       EQUIPEMENTS  CRUD
       ================================================================ */

    #[Route('/equipements', name: 'admin_marketplace_equipements')]
    public function equipements(Request $request, EquipementRepository $repo): Response
    {
        $equipement = new Equipement();
        $form = $this->createForm(EquipementType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $equipement, 'equipements');
            $this->em->persist($equipement);
            $this->em->flush();
            $this->addFlash('success', 'Équipement ajouté avec succès.');
            return $this->redirectToRoute('admin_marketplace_equipements');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('admin/marketplace/equipements.html.twig', [
            'equipements' => $repo->findAllWithRelations(),
            'form' => $form,
            'formHasErrors' => $form->isSubmitted() && !$form->isValid(),
        ]);
    }

    #[Route('/equipements/{id}/edit', name: 'admin_marketplace_equipements_edit')]
    public function equipementsEdit(Request $request, Equipement $equipement): Response
    {
        $form = $this->createForm(EquipementType::class, $equipement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $equipement, 'equipements');
            $this->em->flush();
            $this->addFlash('success', 'Équipement modifié avec succès.');
            return $this->redirectToRoute('admin_marketplace_equipements');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('admin/marketplace/equipements_edit.html.twig', [
            'equipement' => $equipement,
            'form' => $form,
        ]);
    }

    #[Route('/equipements/{id}/delete', name: 'admin_marketplace_equipements_delete', methods: ['POST'])]
    public function equipementsDelete(Request $request, Equipement $equipement): Response
    {
        if ($this->isCsrfTokenValid('delete' . $equipement->getId(), $request->request->get('_token'))) {
            $this->em->remove($equipement);
            $this->em->flush();
            $this->addFlash('success', 'Équipement supprimé.');
        }
        return $this->redirectToRoute('admin_marketplace_equipements');
    }

    #[Route('/equipements/analyser-stock', name: 'admin_marketplace_analyser_stock', methods: ['POST'])]
    public function analyserStock(Request $request, EquipementRepository $repo, PdfMailerService $pdfMailer): Response
    {
        if (!$this->isCsrfTokenValid('analyser_stock', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_marketplace_equipements');
        }

        $lowStock = $repo->findLowStock();

        if (empty($lowStock)) {
            $this->addFlash('success', 'Tous les équipements ont un stock suffisant. Aucune alerte à signaler.');
            return $this->redirectToRoute('admin_marketplace_equipements');
        }

        try {
            $pdfMailer->sendAnalyseStock(array_values($lowStock));
            $this->addFlash('success', 'Rapport envoyé ! ' . count($lowStock) . ' équipement(s) en alerte — email envoyé à hamza.slimani@esprit.tn');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'envoi du rapport : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_marketplace_equipements');
    }

    /* ================================================================
       VEHICULES  CRUD
       ================================================================ */

    #[Route('/vehicules', name: 'admin_marketplace_vehicules')]
    public function vehicules(Request $request, VehiculeRepository $repo): Response
    {
        $vehicule = new Vehicule();
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $vehicule, 'vehicules');
            $this->em->persist($vehicule);
            $this->em->flush();
            $this->addFlash('success', 'Véhicule ajouté avec succès.');
            return $this->redirectToRoute('admin_marketplace_vehicules');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('admin/marketplace/vehicules.html.twig', [
            'vehicules' => $repo->findAllWithRelations(),
            'form' => $form,
            'formHasErrors' => $form->isSubmitted() && !$form->isValid(),
        ]);
    }

    #[Route('/vehicules/{id}/edit', name: 'admin_marketplace_vehicules_edit')]
    public function vehiculesEdit(Request $request, Vehicule $vehicule): Response
    {
        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $vehicule, 'vehicules');
            $this->em->flush();
            $this->addFlash('success', 'Véhicule modifié avec succès.');
            return $this->redirectToRoute('admin_marketplace_vehicules');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('admin/marketplace/vehicules_edit.html.twig', [
            'vehicule' => $vehicule,
            'form' => $form,
        ]);
    }

    #[Route('/vehicules/{id}/delete', name: 'admin_marketplace_vehicules_delete', methods: ['POST'])]
    public function vehiculesDelete(Request $request, Vehicule $vehicule): Response
    {
        if ($this->isCsrfTokenValid('delete' . $vehicule->getId(), $request->request->get('_token'))) {
            $this->em->remove($vehicule);
            $this->em->flush();
            $this->addFlash('success', 'Véhicule supprimé.');
        }
        return $this->redirectToRoute('admin_marketplace_vehicules');
    }

    /* ================================================================
       TERRAINS  CRUD
       ================================================================ */

    #[Route('/terrains', name: 'admin_marketplace_terrains')]
    public function terrains(Request $request, TerrainRepository $repo): Response
    {
        $terrain = new Terrain();
        $form = $this->createForm(TerrainType::class, $terrain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $terrain, 'terrains');
            $this->em->persist($terrain);
            $this->em->flush();
            $this->addFlash('success', 'Terrain ajouté avec succès.');
            return $this->redirectToRoute('admin_marketplace_terrains');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('admin/marketplace/terrains.html.twig', [
            'terrains' => $repo->findAllWithRelations(),
            'form' => $form,
            'formHasErrors' => $form->isSubmitted() && !$form->isValid(),
        ]);
    }

    #[Route('/terrains/{id}/edit', name: 'admin_marketplace_terrains_edit')]
    public function terrainsEdit(Request $request, Terrain $terrain): Response
    {
        $form = $this->createForm(TerrainType::class, $terrain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $terrain, 'terrains');
            $this->em->flush();
            $this->addFlash('success', 'Terrain modifié avec succès.');
            return $this->redirectToRoute('admin_marketplace_terrains');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('admin/marketplace/terrains_edit.html.twig', [
            'terrain' => $terrain,
            'form' => $form,
        ]);
    }

    #[Route('/terrains/{id}/delete', name: 'admin_marketplace_terrains_delete', methods: ['POST'])]
    public function terrainsDelete(Request $request, Terrain $terrain): Response
    {
        if ($this->isCsrfTokenValid('delete' . $terrain->getId(), $request->request->get('_token'))) {
            $this->em->remove($terrain);
            $this->em->flush();
            $this->addFlash('success', 'Terrain supprimé.');
        }
        return $this->redirectToRoute('admin_marketplace_terrains');
    }

    /* ================================================================
       FOURNISSEURS  CRUD
       ================================================================ */

    #[Route('/fournisseurs', name: 'admin_marketplace_fournisseurs')]
    public function fournisseurs(Request $request, FournisseurRepository $repo): Response
    {
        $fournisseur = new Fournisseur();
        $form = $this->createForm(FournisseurType::class, $fournisseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($fournisseur);
            $this->em->flush();
            $this->addFlash('success', 'Fournisseur ajouté avec succès.');
            return $this->redirectToRoute('admin_marketplace_fournisseurs');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('admin/marketplace/fournisseurs.html.twig', [
            'fournisseurs' => $repo->findBy([], ['dateCreation' => 'DESC']),
            'form' => $form,
            'formHasErrors' => $form->isSubmitted() && !$form->isValid(),
        ]);
    }

    #[Route('/fournisseurs/{id}/edit', name: 'admin_marketplace_fournisseurs_edit')]
    public function fournisseursEdit(Request $request, Fournisseur $fournisseur): Response
    {
        $form = $this->createForm(FournisseurType::class, $fournisseur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'Fournisseur modifié avec succès.');
            return $this->redirectToRoute('admin_marketplace_fournisseurs');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('admin/marketplace/fournisseurs_edit.html.twig', [
            'fournisseur' => $fournisseur,
            'form' => $form,
        ]);
    }

    #[Route('/fournisseurs/{id}/delete', name: 'admin_marketplace_fournisseurs_delete', methods: ['POST'])]
    public function fournisseursDelete(Request $request, Fournisseur $fournisseur): Response
    {
        if ($this->isCsrfTokenValid('delete' . $fournisseur->getId(), $request->request->get('_token'))) {
            $this->em->remove($fournisseur);
            $this->em->flush();
            $this->addFlash('success', 'Fournisseur supprimé.');
        }
        return $this->redirectToRoute('admin_marketplace_fournisseurs');
    }

    /* ================================================================
       COMMANDES  (view + update status + delete)
       ================================================================ */

    #[Route('/commandes', name: 'admin_marketplace_commandes')]
    public function commandes(CommandeRepository $repo): Response
    {
        return $this->render('admin/marketplace/commandes.html.twig', [
            'commandes' => $repo->findAllWithUser(),
        ]);
    }

    #[Route('/commandes/{id}/edit', name: 'admin_marketplace_commandes_edit')]
    public function commandesEdit(Request $request, Commande $commande): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('commande_status' . $commande->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('admin_marketplace_commandes');
            }

            $commande->setStatutPaiement($request->request->get('statut_paiement', $commande->getStatutPaiement()));
            $commande->setStatutLivraison($request->request->get('statut_livraison', $commande->getStatutLivraison()));

            $dateLivraison = $request->request->get('date_livraison');
            if ($dateLivraison) {
                $commande->setDateLivraison(new \DateTime($dateLivraison));
            }

            $commande->setNotes($request->request->get('notes', $commande->getNotes()));
            $this->em->flush();
            $this->addFlash('success', 'Commande mise à jour.');
            return $this->redirectToRoute('admin_marketplace_commandes');
        }

        return $this->render('admin/marketplace/commandes_edit.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/commandes/{id}/delete', name: 'admin_marketplace_commandes_delete', methods: ['POST'])]
    public function commandesDelete(Request $request, Commande $commande): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commande->getId(), $request->request->get('_token'))) {
            $this->em->remove($commande);
            $this->em->flush();
            $this->addFlash('success', 'Commande supprimée.');
        }
        return $this->redirectToRoute('admin_marketplace_commandes');
    }

    /* ================================================================
       LOCATIONS  (view + update status + delete)
       ================================================================ */

    #[Route('/locations', name: 'admin_marketplace_locations')]
    public function locations(LocationRepository $repo): Response
    {
        return $this->render('admin/marketplace/locations.html.twig', [
            'locations' => $repo->findAllWithRelations(),
        ]);
    }

    #[Route('/locations/{id}/edit', name: 'admin_marketplace_locations_edit')]
    public function locationsEdit(Request $request, Location $location): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('location_status' . $location->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('admin_marketplace_locations');
            }

            $location->setStatut($request->request->get('statut', $location->getStatut()));
            $location->setNotes($request->request->get('notes', $location->getNotes()));
            $this->em->flush();
            $this->addFlash('success', 'Location mise à jour.');
            return $this->redirectToRoute('admin_marketplace_locations');
        }

        return $this->render('admin/marketplace/locations_edit.html.twig', [
            'location' => $location,
        ]);
    }

    #[Route('/locations/{id}/delete', name: 'admin_marketplace_locations_delete', methods: ['POST'])]
    public function locationsDelete(Request $request, Location $location): Response
    {
        if ($this->isCsrfTokenValid('delete' . $location->getId(), $request->request->get('_token'))) {
            $this->em->remove($location);
            $this->em->flush();
            $this->addFlash('success', 'Location supprimée.');
        }
        return $this->redirectToRoute('admin_marketplace_locations');
    }

    /* ================================================================
       HELPERS
       ================================================================ */

    private function handleImageUpload($form, object $entity, string $subfolder): void
    {
        $file = $form->get('imageFile')->getData();
        if (!$file) {
            return;
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/marketplace/' . $subfolder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        try {
            $file->move($uploadDir, $newFilename);
            $entity->setImageUrl('uploads/marketplace/' . $subfolder . '/' . $newFilename);
        } catch (FileException $e) {
            $this->addFlash('danger', 'Erreur lors du téléchargement de l\'image.');
        }
    }
}
