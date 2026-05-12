<?php

namespace App\Controller\Integration;

use App\Entity\Marketplace\Equipement;
use App\Entity\Marketplace\Categorie;
use App\Entity\Marketplace\Fournisseur;
use App\Repository\Marketplace\EquipementRepository;
use App\Repository\Marketplace\CategorieRepository;
use App\Repository\Marketplace\FournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/integration/equipements', name: 'integration_equipement_')]
class EquipementIntegrationController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly EquipementRepository $repo,
        private readonly CategorieRepository $catRepo,
        private readonly FournisseurRepository $foRepo,
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
        $e = $this->repo->find($id);
        if (!$e) { return $this->notFound("Équipement $id introuvable"); }
        return $this->ok($this->serialize($e));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $equip = new Equipement();
        $err = $this->hydrate($equip, $d);
        if ($err) { return $this->badRequest($err); }

        $this->em->persist($equip);
        $this->em->flush();

        return $this->created($this->serialize($equip));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $equip = $this->repo->find($id);
        if (!$equip) { return $this->notFound("Équipement $id introuvable"); }

        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $err = $this->hydrate($equip, $d);
        if ($err) { return $this->badRequest($err); }

        $this->em->flush();

        return $this->ok($this->serialize($equip));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $e = $this->repo->find($id);
        if (!$e) { return $this->notFound("Équipement $id introuvable"); }

        $this->em->remove($e);
        $this->em->flush();

        return $this->noContent();
    }

    private function serialize(Equipement $e): array
    {
        return [
            'id'             => $e->getId(),
            'categorie_id'   => $e->getCategorie()?->getId(),
            'categorie_nom'  => $e->getCategorie()?->getNom(),
            'fournisseur_id' => $e->getFournisseur()?->getId(),
            'fournisseur_nom'=> $e->getFournisseur()?->getNomEntreprise(),
            'nom'            => $e->getNom(),
            'description'    => $e->getDescription(),
            'prix_achat'     => $e->getPrixAchat(),
            'prix_vente'     => $e->getPrixVente(),
            'quantite_stock' => $e->getQuantiteStock(),
            'seuil_alerte'   => $e->getSeuilAlerte(),
            'image_url'      => $e->getImageUrl(),
            'disponible'     => $e->isDisponible(),
            'date_creation'  => $e->getDateCreation()?->format('Y-m-d H:i:s'),
        ];
    }

    /** Returns error string or null on success */
    private function hydrate(Equipement $e, array $d): ?string
    {
        if (isset($d['categorie_id'])) {
            $cat = $this->catRepo->find((int)$d['categorie_id']);
            if (!$cat) { return "Catégorie {$d['categorie_id']} introuvable"; }
            $e->setCategorie($cat);
        }
        if (isset($d['fournisseur_id'])) {
            $fo = $this->foRepo->find((int)$d['fournisseur_id']);
            if (!$fo) { return "Fournisseur {$d['fournisseur_id']} introuvable"; }
            $e->setFournisseur($fo);
        }
        if (isset($d['nom']))            $e->setNom($d['nom']);
        if (array_key_exists('description', $d)) $e->setDescription($d['description']);
        if (isset($d['prix_achat']))     $e->setPrixAchat((string)$d['prix_achat']);
        if (isset($d['prix_vente']))     $e->setPrixVente((string)$d['prix_vente']);
        if (isset($d['quantite_stock'])) $e->setQuantiteStock((int)$d['quantite_stock']);
        if (isset($d['seuil_alerte']))   $e->setSeuilAlerte((int)$d['seuil_alerte']);
        if (array_key_exists('image_url', $d)) $e->setImageUrl($d['image_url']);
        if (isset($d['disponible']))     $e->setDisponible((bool)$d['disponible']);
        return null;
    }
}
