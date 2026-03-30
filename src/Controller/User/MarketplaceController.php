<?php

namespace App\Controller\User;

use App\Entity\Marketplace\Commande;
use App\Entity\Marketplace\DetailCommande;
use App\Entity\Marketplace\Location;
use App\Repository\Marketplace\EquipementRepository;
use App\Repository\Marketplace\LocationRepository;
use App\Repository\Marketplace\TerrainRepository;
use App\Repository\Marketplace\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/marketplace')]
#[IsGranted('ROLE_USER')]
class MarketplaceController extends AbstractController
{
    public function __construct(
        private RequestStack $requestStack,
    ) {}

    /* ================================================================
       CATALOGUE — Main page
       ================================================================ */

    #[Route('', name: 'user_marketplace', methods: ['GET'])]
    public function index(
        EquipementRepository $equipRepo,
        VehiculeRepository $vehicRepo,
        TerrainRepository $terrainRepo,
    ): Response {
        return $this->render('user/marketplace/index.html.twig', [
            'equipements' => $equipRepo->findBy(['disponible' => true]),
            'vehicules' => $vehicRepo->findBy(['disponible' => true]),
            'terrains' => $terrainRepo->findBy(['disponible' => true]),
            'cart' => $this->cartSummary($this->getCart()),
            'locations' => $this->locSummary($this->getLocationsSession()),
        ]);
    }

    /* ================================================================
       PANIER (Équipements) — Session AJAX
       ================================================================ */

    #[Route('/panier/ajouter', name: 'user_marketplace_cart_add', methods: ['POST'])]
    public function cartAdd(Request $request, EquipementRepository $equipRepo): JsonResponse
    {
        $id = (int) $request->request->get('id');
        $qty = max(1, (int) $request->request->get('qty', 1));
        $equip = $equipRepo->find($id);

        if (!$equip || !$equip->isDisponible()) {
            return $this->json(['error' => 'Article non disponible'], 400);
        }

        $cart = $this->getCart();
        $current = $cart[$id]['qty'] ?? 0;

        if ($current + $qty > $equip->getQuantiteStock()) {
            return $this->json(['error' => 'Stock insuffisant (disponible : ' . $equip->getQuantiteStock() . ')'], 400);
        }

        $cart[$id] = [
            'qty' => $current + $qty,
            'nom' => $equip->getNom(),
            'prix' => $equip->getPrixVente(),
            'image' => $equip->getImageUrl(),
            'stock' => $equip->getQuantiteStock(),
        ];
        $this->saveCart($cart);

        return $this->json(['success' => true, 'cart' => $this->cartSummary($cart)]);
    }

    #[Route('/panier/modifier', name: 'user_marketplace_cart_update', methods: ['POST'])]
    public function cartUpdate(Request $request, EquipementRepository $equipRepo): JsonResponse
    {
        $id = (int) $request->request->get('id');
        $qty = max(0, (int) $request->request->get('qty'));
        $cart = $this->getCart();

        if ($qty === 0) {
            unset($cart[$id]);
        } else {
            $equip = $equipRepo->find($id);
            if (!$equip || $qty > $equip->getQuantiteStock()) {
                return $this->json(['error' => 'Stock insuffisant'], 400);
            }
            if (isset($cart[$id])) {
                $cart[$id]['qty'] = $qty;
            }
        }
        $this->saveCart($cart);

        return $this->json(['success' => true, 'cart' => $this->cartSummary($cart)]);
    }

    #[Route('/panier/supprimer', name: 'user_marketplace_cart_remove', methods: ['POST'])]
    public function cartRemove(Request $request): JsonResponse
    {
        $id = (int) $request->request->get('id');
        $cart = $this->getCart();
        unset($cart[$id]);
        $this->saveCart($cart);

        return $this->json(['success' => true, 'cart' => $this->cartSummary($cart)]);
    }

    #[Route('/panier', name: 'user_marketplace_cart_get', methods: ['GET'])]
    public function cartGet(): JsonResponse
    {
        return $this->json(['cart' => $this->cartSummary($this->getCart())]);
    }

    /* ================================================================
       LOCATIONS SESSION (Véhicules + Terrains)
       ================================================================ */

    #[Route('/locations/ajouter', name: 'user_marketplace_loc_add', methods: ['POST'])]
    public function locAdd(Request $request, VehiculeRepository $vehicRepo, TerrainRepository $terrainRepo): JsonResponse
    {
        $type = $request->request->get('type'); // 'vehicule' or 'terrain'
        $id = (int) $request->request->get('id');
        $dateDebut = $request->request->get('dateDebut');
        $dateFin = $request->request->get('dateFin');

        if (!in_array($type, ['vehicule', 'terrain'], true)) {
            return $this->json(['error' => 'Type invalide'], 400);
        }

        $start = \DateTime::createFromFormat('Y-m-d', $dateDebut);
        $end = \DateTime::createFromFormat('Y-m-d', $dateFin);

        if (!$start || !$end || $end <= $start) {
            return $this->json(['error' => 'Dates invalides (la date fin doit être après la date début)'], 400);
        }

        $days = (int) $start->diff($end)->days;

        if ($type === 'vehicule') {
            $item = $vehicRepo->find($id);
            if (!$item || !$item->isDisponible()) return $this->json(['error' => 'Véhicule non disponible'], 400);
            $prixJour = (float) $item->getPrixJour();
            $caution = (float) $item->getCaution();
            $total = $prixJour * $days;
            $entry = [
                'type' => 'vehicule', 'id' => $id,
                'nom' => $item->getNom(), 'image' => $item->getImageUrl(),
                'marque' => $item->getMarque(), 'modele' => $item->getModele(),
                'dateDebut' => $dateDebut, 'dateFin' => $dateFin,
                'jours' => $days, 'prixJour' => $prixJour,
                'caution' => $caution, 'total' => $total,
            ];
        } else {
            $item = $terrainRepo->find($id);
            if (!$item || !$item->isDisponible()) return $this->json(['error' => 'Terrain non disponible'], 400);
            $prixMois = (float) $item->getPrixMois();
            $caution = (float) $item->getCaution();
            $totalMonths = $days / 30;
            $total = $prixMois * $totalMonths;
            $entry = [
                'type' => 'terrain', 'id' => $id,
                'nom' => $item->getTitre(), 'image' => $item->getImageUrl(),
                'ville' => $item->getVille(),
                'dateDebut' => $dateDebut, 'dateFin' => $dateFin,
                'jours' => $days, 'prixMois' => $prixMois,
                'caution' => $caution, 'total' => round($total, 2),
            ];
        }

        $locs = $this->getLocationsSession();
        $key = $type . '_' . $id;
        $locs[$key] = $entry;
        $this->saveLocationsSession($locs);

        return $this->json(['success' => true, 'locations' => $this->locSummary($locs)]);
    }

    #[Route('/locations/supprimer', name: 'user_marketplace_loc_remove', methods: ['POST'])]
    public function locRemove(Request $request): JsonResponse
    {
        $key = $request->request->get('key');
        $locs = $this->getLocationsSession();
        unset($locs[$key]);
        $this->saveLocationsSession($locs);

        return $this->json(['success' => true, 'locations' => $this->locSummary($locs)]);
    }

    /* ================================================================
       PAIEMENT — Équipements
       ================================================================ */

    #[Route('/paiement', name: 'user_marketplace_paiement', methods: ['GET'])]
    public function paiementEquipements(): Response
    {
        $cart = $this->getCart();
        if (empty($cart)) return $this->redirectToRoute('user_marketplace');

        $total = 0;
        foreach ($cart as $item) {
            $total += (float) $item['prix'] * $item['qty'];
        }

        // Convert TND → EUR (÷ 3.4) then to cents
        $amountEur = round($total / 3.4, 2);
        $stripeCents = (int) round($amountEur * 100);

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        $intent = PaymentIntent::create([
            'amount' => $stripeCents,
            'currency' => 'eur',
            'metadata' => ['type' => 'equipement', 'total_tnd' => $total],
        ]);

        // Store intent ID in session for verification
        $this->requestStack->getSession()->set('stripe_pi_equip', $intent->id);

        return $this->render('user/marketplace/paiement.html.twig', [
            'cart' => $cart,
            'total' => $total,
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
            'client_secret' => $intent->client_secret,
        ]);
    }

    #[Route('/paiement', name: 'user_marketplace_paiement_process', methods: ['POST'])]
    public function processPaiementEquipements(
        Request $request,
        EquipementRepository $equipRepo,
        EntityManagerInterface $em,
    ): Response {
        $cart = $this->getCart();
        if (empty($cart)) return $this->redirectToRoute('user_marketplace');

        if (!$this->isCsrfTokenValid('paiement_equipements', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('user_marketplace_paiement');
        }

        // Verify PaymentIntent with Stripe
        $piId = $this->requestStack->getSession()->get('stripe_pi_equip');
        if (!$piId) {
            $this->addFlash('danger', 'Session de paiement expirée. Veuillez réessayer.');
            return $this->redirectToRoute('user_marketplace_paiement');
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        $intent = PaymentIntent::retrieve($piId);

        if ($intent->status !== 'succeeded') {
            $this->addFlash('danger', 'Le paiement n\'a pas été confirmé. Veuillez réessayer.');
            return $this->redirectToRoute('user_marketplace_paiement');
        }

        $user = $this->getUser();
        $commande = new Commande();
        $commande->setUtilisateur($user);
        $commande->setNumeroCommande('CMD-' . strtoupper(uniqid()));
        $commande->setAdresseLivraison($request->request->get('adresse', ''));
        $commande->setVilleLivraison($request->request->get('ville', ''));
        $commande->setStatutPaiement('paye');
        $commande->setStatutLivraison('en_preparation');

        $montantTotal = 0;

        foreach ($cart as $id => $item) {
            $equip = $equipRepo->find($id);
            if (!$equip) continue;

            $detail = new DetailCommande();
            $detail->setCommande($commande);
            $detail->setEquipement($equip);
            $detail->setQuantite($item['qty']);
            $detail->setPrixUnitaire($equip->getPrixVente());
            $sousTotal = (float) $equip->getPrixVente() * $item['qty'];
            $detail->setSousTotal((string) $sousTotal);
            $montantTotal += $sousTotal;

            // Decrease stock
            $equip->setQuantiteStock($equip->getQuantiteStock() - $item['qty']);
            $em->persist($detail);
        }

        $commande->setMontantTotal((string) $montantTotal);
        $em->persist($commande);
        $em->flush();

        $this->saveCart([]);
        $this->requestStack->getSession()->remove('stripe_pi_equip');

        $this->addFlash('success', 'Commande ' . $commande->getNumeroCommande() . ' confirmée ! Merci pour votre achat.');
        return $this->redirectToRoute('user_marketplace');
    }

    /* ================================================================
       PAIEMENT À LA LIVRAISON — Équipements
       ================================================================ */

    #[Route('/paiement-livraison', name: 'user_marketplace_paiement_livraison', methods: ['POST'])]
    public function paiementLivraison(
        Request $request,
        EquipementRepository $equipRepo,
        EntityManagerInterface $em,
    ): Response {
        $cart = $this->getCart();
        if (empty($cart)) {
            return $this->redirectToRoute('user_marketplace');
        }

        if (!$this->isCsrfTokenValid('paiement_equipements', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('user_marketplace_paiement');
        }

        $adresse = trim($request->request->get('adresse', ''));
        $ville   = trim($request->request->get('ville', ''));

        if ($adresse === '' || $ville === '') {
            $this->addFlash('danger', 'Veuillez remplir l\'adresse et la ville de livraison.');
            return $this->redirectToRoute('user_marketplace_paiement');
        }

        $user = $this->getUser();
        $commande = new Commande();
        $commande->setUtilisateur($user);
        $commande->setNumeroCommande('CMD-' . strtoupper(uniqid()));
        $commande->setAdresseLivraison($adresse);
        $commande->setVilleLivraison($ville);
        $commande->setStatutPaiement('en_attente');
        $commande->setStatutLivraison('en_preparation');

        $montantTotal = 0;

        foreach ($cart as $id => $item) {
            $equip = $equipRepo->find($id);
            if (!$equip) continue;

            $detail = new DetailCommande();
            $detail->setCommande($commande);
            $detail->setEquipement($equip);
            $detail->setQuantite($item['qty']);
            $detail->setPrixUnitaire($equip->getPrixVente());
            $sousTotal = (float) $equip->getPrixVente() * $item['qty'];
            $detail->setSousTotal((string) $sousTotal);
            $montantTotal += $sousTotal;

            $equip->setQuantiteStock($equip->getQuantiteStock() - $item['qty']);
            $em->persist($detail);
        }

        $commande->setMontantTotal((string) $montantTotal);
        $em->persist($commande);
        $em->flush();

        $this->saveCart([]);
        $this->requestStack->getSession()->remove('stripe_pi_equip');

        $this->addFlash('success', 'Commande ' . $commande->getNumeroCommande() . ' enregistrée ! Paiement à la livraison.');
        return $this->redirectToRoute('user_marketplace');
    }

    /* ================================================================
       PAIEMENT — Locations
       ================================================================ */

    #[Route('/paiement-locations', name: 'user_marketplace_paiement_locations', methods: ['GET'])]
    public function paiementLocations(): Response
    {
        $locs = $this->getLocationsSession();
        if (empty($locs)) return $this->redirectToRoute('user_marketplace');

        $total = 0;
        $totalCaution = 0;
        foreach ($locs as $loc) {
            $total += $loc['total'];
            $totalCaution += $loc['caution'];
        }

        $grandTotal = $total + $totalCaution;

        // Convert TND → EUR (÷ 3.4) then to cents
        $amountEur = round($grandTotal / 3.4, 2);
        $stripeCents = (int) round($amountEur * 100);

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        $intent = PaymentIntent::create([
            'amount' => $stripeCents,
            'currency' => 'eur',
            'metadata' => ['type' => 'location', 'total_tnd' => $grandTotal],
        ]);

        $this->requestStack->getSession()->set('stripe_pi_loc', $intent->id);

        return $this->render('user/marketplace/paiement_locations.html.twig', [
            'locations' => $locs,
            'total' => $total,
            'totalCaution' => $totalCaution,
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'],
            'client_secret' => $intent->client_secret,
        ]);
    }

    #[Route('/paiement-locations', name: 'user_marketplace_paiement_locations_process', methods: ['POST'])]
    public function processPaiementLocations(
        Request $request,
        VehiculeRepository $vehicRepo,
        TerrainRepository $terrainRepo,
        EntityManagerInterface $em,
    ): Response {
        $locs = $this->getLocationsSession();
        if (empty($locs)) return $this->redirectToRoute('user_marketplace');

        if (!$this->isCsrfTokenValid('paiement_locations', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('user_marketplace_paiement_locations');
        }

        // Verify PaymentIntent with Stripe
        $piId = $this->requestStack->getSession()->get('stripe_pi_loc');
        if (!$piId) {
            $this->addFlash('danger', 'Session de paiement expirée. Veuillez réessayer.');
            return $this->redirectToRoute('user_marketplace_paiement_locations');
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        $intent = PaymentIntent::retrieve($piId);

        if ($intent->status !== 'succeeded') {
            $this->addFlash('danger', 'Le paiement n\'a pas été confirmé. Veuillez réessayer.');
            return $this->redirectToRoute('user_marketplace_paiement_locations');
        }

        $user = $this->getUser();

        foreach ($locs as $loc) {
            $location = new Location();
            $location->setUtilisateur($user);
            $location->setTypeLocation($loc['type']);
            $location->setNumeroLocation('LOC-' . strtoupper(uniqid()));
            $location->setDateDebut(new \DateTime($loc['dateDebut']));
            $location->setDateFin(new \DateTime($loc['dateFin']));
            $location->setDureeJours($loc['jours']);
            $location->setPrixTotal((string) $loc['total']);
            $location->setCaution((string) $loc['caution']);
            $location->setStatut('confirmee');

            if ($loc['type'] === 'vehicule') {
                $v = $vehicRepo->find($loc['id']);
                if ($v) $location->setVehicule($v);
            } else {
                $t = $terrainRepo->find($loc['id']);
                if ($t) $location->setTerrain($t);
            }

            $em->persist($location);
        }

        $em->flush();
        $this->saveLocationsSession([]);
        $this->requestStack->getSession()->remove('stripe_pi_loc');

        $this->addFlash('success', 'Réservation confirmée ! Vos locations ont été enregistrées.');
        return $this->redirectToRoute('user_marketplace');
    }

    /* ================================================================
       CANCEL PAYMENT — Timeout (3 min)
       ================================================================ */

    #[Route('/paiement/annuler', name: 'user_marketplace_paiement_cancel', methods: ['POST'])]
    public function cancelPayment(Request $request): JsonResponse
    {
        $type = $request->request->get('type', 'equipement');
        $sessionKey = $type === 'location' ? 'stripe_pi_loc' : 'stripe_pi_equip';
        $piId = $this->requestStack->getSession()->get($sessionKey);

        if ($piId) {
            try {
                Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
                $intent = PaymentIntent::retrieve($piId);
                if (in_array($intent->status, ['requires_payment_method', 'requires_confirmation', 'requires_action'])) {
                    $intent->cancel();
                }
            } catch (\Exception $e) {
                // Silently ignore — intent may already be canceled
            }
            $this->requestStack->getSession()->remove($sessionKey);
        }

        return $this->json(['success' => true]);
    }

    /* ================================================================
       HELPERS — Session management
       ================================================================ */

    private function getCart(): array
    {
        return $this->requestStack->getSession()->get('marketplace_cart', []);
    }

    private function saveCart(array $cart): void
    {
        $this->requestStack->getSession()->set('marketplace_cart', $cart);
    }

    private function cartSummary(array $cart): array
    {
        $items = [];
        $total = 0;
        $count = 0;
        foreach ($cart as $id => $item) {
            $sub = (float) $item['prix'] * $item['qty'];
            $items[] = [
                'id' => $id,
                'nom' => $item['nom'],
                'prix' => $item['prix'],
                'image' => $item['image'],
                'qty' => $item['qty'],
                'stock' => $item['stock'],
                'sousTotal' => round($sub, 2),
            ];
            $total += $sub;
            $count += $item['qty'];
        }
        return ['items' => $items, 'total' => round($total, 2), 'count' => $count];
    }

    private function getLocationsSession(): array
    {
        return $this->requestStack->getSession()->get('marketplace_locations', []);
    }

    private function saveLocationsSession(array $locs): void
    {
        $this->requestStack->getSession()->set('marketplace_locations', $locs);
    }

    private function locSummary(array $locs): array
    {
        $items = [];
        $total = 0;
        $totalCaution = 0;
        foreach ($locs as $key => $loc) {
            $loc['key'] = $key;
            $items[] = $loc;
            $total += $loc['total'];
            $totalCaution += $loc['caution'];
        }
        return ['items' => $items, 'total' => round($total, 2), 'totalCaution' => round($totalCaution, 2), 'count' => count($items)];
    }
}
