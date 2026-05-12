<?php

namespace App\Controller\Integration;

use App\Entity\Marketplace\Terrain;
use App\Entity\Marketplace\Categorie;
use App\Repository\Marketplace\TerrainRepository;
use App\Repository\Marketplace\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/integration/terrains', name: 'integration_terrain_')]
class TerrainIntegrationController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly TerrainRepository $repo,
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
        $t = $this->repo->find($id);
        if (!$t) { return $this->notFound("Terrain $id introuvable"); }
        return $this->ok($this->serialize($t));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $t = new Terrain();
        $err = $this->hydrate($t, $d);
        if ($err) { return $this->badRequest($err); }

        $this->em->persist($t);
        $this->em->flush();

        return $this->created($this->serialize($t));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $t = $this->repo->find($id);
        if (!$t) { return $this->notFound("Terrain $id introuvable"); }

        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $err = $this->hydrate($t, $d);
        if ($err) { return $this->badRequest($err); }

        $this->em->flush();

        return $this->ok($this->serialize($t));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $t = $this->repo->find($id);
        if (!$t) { return $this->notFound("Terrain $id introuvable"); }

        $this->em->remove($t);
        $this->em->flush();

        return $this->noContent();
    }

    private function serialize(Terrain $t): array
    {
        return [
            'id'                  => $t->getId(),
            'categorie_id'        => $t->getCategorie()?->getId(),
            'categorie_nom'       => $t->getCategorie()?->getNom(),
            'titre'               => $t->getTitre(),
            'description'         => $t->getDescription(),
            'superficie_hectares' => $t->getSuperficieHectares(),
            'ville'               => $t->getVille(),
            'adresse'             => $t->getAdresse(),
            'prix_mois'           => $t->getPrixMois(),
            'prix_annee'          => $t->getPrixAnnee(),
            'caution'             => $t->getCaution(),
            'image_url'           => $t->getImageUrl(),
            'disponible'          => $t->isDisponible(),
            'date_creation'       => $t->getDateCreation()?->format('Y-m-d H:i:s'),
        ];
    }

    private function hydrate(Terrain $t, array $d): ?string
    {
        if (isset($d['categorie_id'])) {
            $cat = $this->catRepo->find((int)$d['categorie_id']);
            if (!$cat) { return "Catégorie {$d['categorie_id']} introuvable"; }
            $t->setCategorie($cat);
        }
        if (isset($d['titre']))               $t->setTitre($d['titre']);
        if (array_key_exists('description', $d)) $t->setDescription($d['description']);
        if (isset($d['superficie_hectares'])) $t->setSuperficieHectares((string)$d['superficie_hectares']);
        if (isset($d['ville']))               $t->setVille($d['ville']);
        if (array_key_exists('adresse', $d))  $t->setAdresse($d['adresse']);
        if (array_key_exists('prix_mois', $d))  $t->setPrixMois($d['prix_mois'] !== null ? (string)$d['prix_mois'] : null);
        if (isset($d['prix_annee']))          $t->setPrixAnnee((string)$d['prix_annee']);
        if (isset($d['caution']))             $t->setCaution((string)$d['caution']);
        if (array_key_exists('image_url', $d))   $t->setImageUrl($d['image_url']);
        if (isset($d['disponible']))          $t->setDisponible((bool)$d['disponible']);
        return null;
    }
}
