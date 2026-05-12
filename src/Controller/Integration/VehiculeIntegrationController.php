<?php

namespace App\Controller\Integration;

use App\Entity\Marketplace\Vehicule;
use App\Entity\Marketplace\Categorie;
use App\Repository\Marketplace\VehiculeRepository;
use App\Repository\Marketplace\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/integration/vehicules', name: 'integration_vehicule_')]
class VehiculeIntegrationController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly VehiculeRepository $repo,
        private readonly CategorieRepository $catRepo,
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
        $v = $this->repo->find($id);
        if (!$v) { return $this->notFound("Véhicule $id introuvable"); }
        return $this->ok($this->serialize($v));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $v = new Vehicule();
        $err = $this->hydrate($v, $d);
        if ($err) { return $this->badRequest($err); }

        $this->em->persist($v);
        $this->em->flush();

        return $this->created($this->serialize($v));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $v = $this->repo->find($id);
        if (!$v) { return $this->notFound("Véhicule $id introuvable"); }

        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $err = $this->hydrate($v, $d);
        if ($err) { return $this->badRequest($err); }

        $this->em->flush();

        return $this->ok($this->serialize($v));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $v = $this->repo->find($id);
        if (!$v) { return $this->notFound("Véhicule $id introuvable"); }

        $this->em->remove($v);
        $this->em->flush();

        return $this->noContent();
    }

    private function serialize(Vehicule $v): array
    {
        return [
            'id'             => $v->getId(),
            'categorie_id'   => $v->getCategorie()?->getId(),
            'categorie_nom'  => $v->getCategorie()?->getNom(),
            'nom'            => $v->getNom(),
            'description'    => $v->getDescription(),
            'marque'         => $v->getMarque(),
            'modele'         => $v->getModele(),
            'immatriculation'=> $v->getImmatriculation(),
            'prix_jour'      => $v->getPrixJour(),
            'prix_semaine'   => $v->getPrixSemaine(),
            'prix_mois'      => $v->getPrixMois(),
            'caution'        => $v->getCaution(),
            'image_url'      => $v->getImageUrl(),
            'disponible'     => $v->isDisponible(),
            'date_creation'  => $v->getDateCreation()?->format('Y-m-d H:i:s'),
        ];
    }

    private function hydrate(Vehicule $v, array $d): ?string
    {
        if (isset($d['categorie_id'])) {
            $cat = $this->catRepo->find((int)$d['categorie_id']);
            if (!$cat) { return "Catégorie {$d['categorie_id']} introuvable"; }
            $v->setCategorie($cat);
        }
        if (isset($d['nom']))             $v->setNom($d['nom']);
        if (array_key_exists('description', $d)) $v->setDescription($d['description']);
        if (array_key_exists('marque', $d))     $v->setMarque($d['marque']);
        if (array_key_exists('modele', $d))     $v->setModele($d['modele']);
        if (array_key_exists('immatriculation', $d)) $v->setImmatriculation($d['immatriculation']);
        if (isset($d['prix_jour']))       $v->setPrixJour((string)$d['prix_jour']);
        if (array_key_exists('prix_semaine', $d)) $v->setPrixSemaine($d['prix_semaine'] !== null ? (string)$d['prix_semaine'] : null);
        if (array_key_exists('prix_mois', $d))   $v->setPrixMois($d['prix_mois'] !== null ? (string)$d['prix_mois'] : null);
        if (isset($d['caution']))         $v->setCaution((string)$d['caution']);
        if (array_key_exists('image_url', $d))   $v->setImageUrl($d['image_url']);
        if (isset($d['disponible']))      $v->setDisponible((bool)$d['disponible']);
        return null;
    }
}
