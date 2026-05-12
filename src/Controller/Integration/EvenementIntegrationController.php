<?php

namespace App\Controller\Integration;

use App\Entity\Event\Evenement;
use App\Repository\Event\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/integration/evenements', name: 'integration_evenement_')]
class EvenementIntegrationController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(
        private readonly EvenementRepository $repo,
        private readonly EntityManagerInterface $em,
    ) {}

    // ── GET /integration/evenements ─────────────────────────────────────────
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = array_map([$this, 'serialize'], $this->repo->findAll());
        return $this->ok($items);
    }

    // ── GET /integration/evenements/{id} ────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $e = $this->repo->find($id);
        if (!$e) { return $this->notFound("Événement $id introuvable"); }
        return $this->ok($this->serialize($e));
    }

    // ── POST /integration/evenements ─────────────────────────────────────────
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $d = $this->decodeBody($request);
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        $event = new Evenement();
        $this->hydrate($event, $d);
        $this->em->persist($event);
        $this->em->flush();

        return $this->created($this->serialize($event));
    }

    // ── PUT /integration/evenements/{id} ────────────────────────────────────
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $event = $this->repo->find($id);
        if (!$event) { return $this->notFound("Événement $id introuvable"); }

        try {
            $d = $this->decodeBody($request);
        } catch (\InvalidArgumentException $e) {
            return $this->badRequest($e->getMessage());
        }

        $this->hydrate($event, $d);
        $this->em->flush();

        return $this->ok($this->serialize($event));
    }

    // ── DELETE /integration/evenements/{id} ─────────────────────────────────
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $event = $this->repo->find($id);
        if (!$event) { return $this->notFound("Événement $id introuvable"); }

        $this->em->remove($event);
        $this->em->flush();

        return $this->noContent();
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function serialize(Evenement $e): array
    {
        return [
            'id_evenement'      => $e->getIdEvenement(),
            'titre'             => $e->getTitre(),
            'description'       => $e->getDescription(),
            'image_url'         => $e->getImageUrl(),
            'type_evenement'    => $e->getTypeEvenement(),
            'date_debut'        => $e->getDateDebut()?->format('Y-m-d H:i:s'),
            'date_fin'          => $e->getDateFin()?->format('Y-m-d H:i:s'),
            'horaire_debut'     => $e->getHoraireDebut()?->format('H:i:s'),
            'horaire_fin'       => $e->getHoraireFin()?->format('H:i:s'),
            'lieu'              => $e->getLieu(),
            'adresse'           => $e->getAdresse(),
            'latitude'          => $e->getLatitude(),
            'longitude'         => $e->getLongitude(),
            'capacite_max'      => $e->getCapaciteMax(),
            'places_disponibles'=> $e->getPlacesDisponibles(),
            'organisateur'      => $e->getOrganisateur(),
            'contact_email'     => $e->getContactEmail(),
            'contact_tel'       => $e->getContactTel(),
            'statut'            => $e->getStatut(),
            'date_creation'     => $e->getDateCreation()?->format('Y-m-d H:i:s'),
            'date_modification' => $e->getDateModification()?->format('Y-m-d H:i:s'),
        ];
    }

    private function hydrate(Evenement $e, array $d): void
    {
        if (isset($d['titre']))          $e->setTitre($d['titre']);
        if (isset($d['description']))    $e->setDescription($d['description']);
        if (isset($d['image_url']))      $e->setImageUrl($d['image_url']);
        if (isset($d['type_evenement'])) $e->setTypeEvenement($d['type_evenement']);
        if (isset($d['lieu']))           $e->setLieu($d['lieu']);
        if (isset($d['adresse']))        $e->setAdresse($d['adresse']);
        if (isset($d['latitude']))       $e->setLatitude($d['latitude']);
        if (isset($d['longitude']))      $e->setLongitude($d['longitude']);
        if (isset($d['capacite_max']))   $e->setCapaciteMax((int)$d['capacite_max']);
        if (isset($d['organisateur']))   $e->setOrganisateur($d['organisateur']);
        if (isset($d['contact_email']))  $e->setContactEmail($d['contact_email']);
        if (isset($d['contact_tel']))    $e->setContactTel($d['contact_tel']);
        if (isset($d['statut']))         $e->setStatut($d['statut']);
        if (!empty($d['date_debut']))
            $e->setDateDebut(new \DateTime($d['date_debut']));
        if (!empty($d['date_fin']))
            $e->setDateFin(new \DateTime($d['date_fin']));
        if (!empty($d['horaire_debut']))
            $e->setHoraireDebut(new \DateTime($d['horaire_debut']));
        if (!empty($d['horaire_fin']))
            $e->setHoraireFin(new \DateTime($d['horaire_fin']));
    }
}
