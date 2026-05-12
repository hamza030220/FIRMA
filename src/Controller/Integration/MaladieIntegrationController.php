<?php

namespace App\Controller\Integration;

use App\Entity\Maladie\Maladie;
use App\Repository\Maladie\MaladieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/integration/maladies', name: 'integration_maladie_')]
class MaladieIntegrationController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly MaladieRepository $repo,
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
        $m = $this->repo->find($id);
        if (!$m) { return $this->notFound("Maladie $id introuvable"); }
        return $this->ok($this->serialize($m));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $m = new Maladie();
        $this->hydrate($m, $d);
        $this->em->persist($m);
        $this->em->flush();

        return $this->created($this->serialize($m));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $m = $this->repo->find($id);
        if (!$m) { return $this->notFound("Maladie $id introuvable"); }

        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $this->hydrate($m, $d);
        $this->em->flush();

        return $this->ok($this->serialize($m));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $m = $this->repo->find($id);
        if (!$m) { return $this->notFound("Maladie $id introuvable"); }

        $this->em->remove($m);
        $this->em->flush();

        return $this->noContent();
    }

    private function serialize(Maladie $m): array
    {
        return [
            'id'              => $m->getId(),
            'nom'             => $m->getNom(),
            'nom_scientifique'=> $m->getNomScientifique(),
            'description'     => $m->getDescription(),
            'symptomes'       => $m->getSymptomes(),
            'image_url'       => $m->getImageUrl(),
            'niveau_gravite'  => $m->getNiveauGravite(),
            'saison_frequente'=> $m->getSaisonFrequente(),
            'temp_min'        => $m->getTempMin(),
            'temp_max'        => $m->getTempMax(),
            'humidite_min'    => $m->getHumiditeMin(),
        ];
    }

    private function hydrate(Maladie $m, array $d): void
    {
        if (isset($d['nom']))              $m->setNom($d['nom']);
        if (array_key_exists('nom_scientifique', $d)) $m->setNomScientifique($d['nom_scientifique']);
        if (isset($d['description']))      $m->setDescription($d['description']);
        if (isset($d['symptomes']))        $m->setSymptomes($d['symptomes']);
        if (array_key_exists('image_url', $d)) $m->setImageUrl($d['image_url']);
        if (isset($d['niveau_gravite']))   $m->setNiveauGravite($d['niveau_gravite']);
        if (array_key_exists('saison_frequente', $d)) $m->setSaisonFrequente($d['saison_frequente']);
        if (array_key_exists('temp_min', $d))  $m->setTempMin($d['temp_min']);
        if (array_key_exists('temp_max', $d))  $m->setTempMax($d['temp_max']);
        if (array_key_exists('humidite_min', $d)) $m->setHumiditeMin($d['humidite_min'] !== null ? (int)$d['humidite_min'] : null);
    }
}
