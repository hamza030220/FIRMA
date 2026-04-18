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
