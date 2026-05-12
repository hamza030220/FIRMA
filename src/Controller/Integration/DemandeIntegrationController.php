<?php

namespace App\Controller\Integration;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/integration/demandes', name: 'integration_demande_')]
class DemandeIntegrationController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(private readonly Connection $db) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $rows = $this->db->fetchAllAssociative('SELECT * FROM demande ORDER BY id_demande');
        return $this->ok($rows);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $row = $this->db->fetchAssociative('SELECT * FROM demande WHERE id_demande = ?', [$id]);
        if (!$row) { return $this->notFound("Demande $id introuvable"); }
        return $this->ok($row);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $this->db->insert('demande', $this->filterFields($d));
        $id = (int)$this->db->lastInsertId();

        $row = $this->db->fetchAssociative('SELECT * FROM demande WHERE id_demande = ?', [$id]);
        return $this->created($row);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $exists = $this->db->fetchOne('SELECT id_demande FROM demande WHERE id_demande = ?', [$id]);
        if (!$exists) { return $this->notFound("Demande $id introuvable"); }

        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $this->db->update('demande', $this->filterFields($d), ['id_demande' => $id]);
        $row = $this->db->fetchAssociative('SELECT * FROM demande WHERE id_demande = ?', [$id]);
        return $this->ok($row);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $exists = $this->db->fetchOne('SELECT id_demande FROM demande WHERE id_demande = ?', [$id]);
        if (!$exists) { return $this->notFound("Demande $id introuvable"); }

        $this->db->delete('demande', ['id_demande' => $id]);
        return $this->noContent();
    }

    private function filterFields(array $d): array
    {
        $allowed = ['id_utilisateur','type_probleme','description','date_demande','statut','id_tech','adresse_client'];
        return array_intersect_key($d, array_flip($allowed));
    }
}
