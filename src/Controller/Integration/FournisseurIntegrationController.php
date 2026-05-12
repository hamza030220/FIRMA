<?php

namespace App\Controller\Integration;

use App\Entity\Marketplace\Fournisseur;
use App\Repository\Marketplace\FournisseurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/integration/fournisseurs', name: 'integration_fournisseur_')]
class FournisseurIntegrationController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly FournisseurRepository $repo,
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
        $f = $this->repo->find($id);
        if (!$f) { return $this->notFound("Fournisseur $id introuvable"); }
        return $this->ok($this->serialize($f));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $f = new Fournisseur();
        $this->hydrate($f, $d);
        $this->em->persist($f);
        $this->em->flush();

        return $this->created($this->serialize($f));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $f = $this->repo->find($id);
        if (!$f) { return $this->notFound("Fournisseur $id introuvable"); }

        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $this->hydrate($f, $d);
        $this->em->flush();

        return $this->ok($this->serialize($f));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $f = $this->repo->find($id);
        if (!$f) { return $this->notFound("Fournisseur $id introuvable"); }

        $this->em->remove($f);
        $this->em->flush();

        return $this->noContent();
    }

    private function serialize(Fournisseur $f): array
    {
        return [
            'id'            => $f->getId(),
            'nom_entreprise'=> $f->getNomEntreprise(),
            'contact_nom'   => $f->getContactNom(),
            'email'         => $f->getEmail(),
            'telephone'     => $f->getTelephone(),
            'adresse'       => $f->getAdresse(),
            'ville'         => $f->getVille(),
            'actif'         => $f->isActif(),
            'date_creation' => $f->getDateCreation()?->format('Y-m-d H:i:s'),
        ];
    }

    private function hydrate(Fournisseur $f, array $d): void
    {
        if (isset($d['nom_entreprise'])) $f->setNomEntreprise($d['nom_entreprise']);
        if (array_key_exists('contact_nom', $d)) $f->setContactNom($d['contact_nom']);
        if (array_key_exists('email', $d))     $f->setEmail($d['email']);
        if (array_key_exists('telephone', $d)) $f->setTelephone($d['telephone']);
        if (array_key_exists('adresse', $d))   $f->setAdresse($d['adresse']);
        if (array_key_exists('ville', $d))     $f->setVille($d['ville']);
        if (isset($d['actif']))                $f->setActif((bool)$d['actif']);
    }
}
