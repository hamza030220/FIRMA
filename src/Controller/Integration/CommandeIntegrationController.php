<?php

namespace App\Controller\Integration;

use App\Entity\Marketplace\Commande;
use App\Entity\User\Utilisateur;
use App\Repository\Marketplace\CommandeRepository;
use App\Repository\User\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/integration/commandes', name: 'integration_commande_')]
class CommandeIntegrationController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CommandeRepository $repo,
        private readonly UtilisateurRepository $userRepo,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->ok(array_map([$this, 'serialize'], $this->repo->findAll()));
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $c = $this->repo->find($id);
        if (!$c) { return $this->notFound("Commande $id introuvable"); }
        return $this->ok($this->serialize($c));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $c = new Commande();
        $err = $this->hydrate($c, $d);
        if ($err) { return $this->badRequest($err); }

        $this->em->persist($c);
        $this->em->flush();

        return $this->created($this->serialize($c));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $c = $this->repo->find($id);
        if (!$c) { return $this->notFound("Commande $id introuvable"); }

        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $err = $this->hydrate($c, $d);
        if ($err) { return $this->badRequest($err); }

        $this->em->flush();

        return $this->ok($this->serialize($c));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $c = $this->repo->find($id);
        if (!$c) { return $this->notFound("Commande $id introuvable"); }

        $this->em->remove($c);
        $this->em->flush();

        return $this->noContent();
    }

    private function serialize(Commande $c): array
    {
        return [
            'id'                => $c->getId(),
            'utilisateur_id'    => $c->getUtilisateur()?->getId(),
            'numero_commande'   => $c->getNumeroCommande(),
            'montant_total'     => $c->getMontantTotal(),
            'statut_paiement'   => $c->getStatutPaiement(),
            'statut_livraison'  => $c->getStatutLivraison(),
            'adresse_livraison' => $c->getAdresseLivraison(),
            'ville_livraison'   => $c->getVilleLivraison(),
            'date_commande'     => $c->getDateCommande()?->format('Y-m-d H:i:s'),
            'date_livraison'    => $c->getDateLivraison()?->format('Y-m-d'),
            'notes'             => $c->getNotes(),
        ];
    }

    private function hydrate(Commande $c, array $d): ?string
    {
        if (isset($d['utilisateur_id'])) {
            $u = $this->userRepo->find((int)$d['utilisateur_id']);
            if (!$u) { return "Utilisateur {$d['utilisateur_id']} introuvable"; }
            $c->setUtilisateur($u);
        }
        if (isset($d['numero_commande']))   $c->setNumeroCommande($d['numero_commande']);
        if (isset($d['montant_total']))     $c->setMontantTotal((string)$d['montant_total']);
        if (isset($d['statut_paiement']))   $c->setStatutPaiement($d['statut_paiement']);
        if (isset($d['statut_livraison']))  $c->setStatutLivraison($d['statut_livraison']);
        if (isset($d['adresse_livraison'])) $c->setAdresseLivraison($d['adresse_livraison']);
        if (array_key_exists('ville_livraison', $d)) $c->setVilleLivraison($d['ville_livraison']);
        if (!empty($d['date_commande']))    $c->setDateCommande(new \DateTime($d['date_commande']));
        if (array_key_exists('date_livraison', $d) && $d['date_livraison'])
            $c->setDateLivraison(new \DateTime($d['date_livraison']));
        if (array_key_exists('notes', $d)) $c->setNotes($d['notes']);
        return null;
    }
}
