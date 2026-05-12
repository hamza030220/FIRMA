<?php

namespace App\Controller\Integration;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/integration/avis', name: 'integration_avis_')]
class AvisIntegrationController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(private readonly Connection $db) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $rows = $this->db->fetchAllAssociative('SELECT * FROM avis ORDER BY id_avis');
        return $this->ok($rows);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $row = $this->db->fetchAssociative('SELECT * FROM avis WHERE id_avis = ?', [$id]);
        if (!$row) { return $this->notFound("Avis $id introuvable"); }
        return $this->ok($row);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $this->db->insert('avis', $this->filterFields($d));
        $id = (int)$this->db->lastInsertId();

        $row = $this->db->fetchAssociative('SELECT * FROM avis WHERE id_avis = ?', [$id]);
        return $this->created($row);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $exists = $this->db->fetchOne('SELECT id_avis FROM avis WHERE id_avis = ?', [$id]);
        if (!$exists) { return $this->notFound("Avis $id introuvable"); }

        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $this->db->update('avis', $this->filterFields($d), ['id_avis' => $id]);
        $row = $this->db->fetchAssociative('SELECT * FROM avis WHERE id_avis = ?', [$id]);
        return $this->ok($row);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $exists = $this->db->fetchOne('SELECT id_avis FROM avis WHERE id_avis = ?', [$id]);
        if (!$exists) { return $this->notFound("Avis $id introuvable"); }

        $this->db->delete('avis', ['id_avis' => $id]);
        return $this->noContent();
    }

    private function filterFields(array $d): array
    {
        $allowed = ['id_utilisateur','note','commentaire','date_avis','id_tech','id_demande'];
        return array_intersect_key($d, array_flip($allowed));
    }
}
