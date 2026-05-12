<?php

namespace App\Controller\Integration;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * CRUD for technicien table (JavaFX-owned entity, no Symfony ORM mapping).
 */
#[Route('/integration/techniciens', name: 'integration_technicien_')]
class TechnicienIntegrationController extends AbstractController
{
    use ApiResponseTrait;

    public function __construct(private readonly Connection $db) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $rows = $this->db->fetchAllAssociative('SELECT * FROM technicien ORDER BY id_tech');
        return $this->ok($rows);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $row = $this->db->fetchAssociative('SELECT * FROM technicien WHERE id_tech = ?', [$id]);
        if (!$row) { return $this->notFound("Technicien $id introuvable"); }
        return $this->ok($row);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $this->db->insert('technicien', $this->filterFields($d));
        $id = (int)$this->db->lastInsertId();

        $row = $this->db->fetchAssociative('SELECT * FROM technicien WHERE id_tech = ?', [$id]);
        return $this->created($row);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $exists = $this->db->fetchOne('SELECT id_tech FROM technicien WHERE id_tech = ?', [$id]);
        if (!$exists) { return $this->notFound("Technicien $id introuvable"); }

        try { $d = $this->decodeBody($request); }
        catch (\InvalidArgumentException $e) { return $this->badRequest($e->getMessage()); }

        $this->db->update('technicien', $this->filterFields($d), ['id_tech' => $id]);
        $row = $this->db->fetchAssociative('SELECT * FROM technicien WHERE id_tech = ?', [$id]);
        return $this->ok($row);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $exists = $this->db->fetchOne('SELECT id_tech FROM technicien WHERE id_tech = ?', [$id]);
        if (!$exists) { return $this->notFound("Technicien $id introuvable"); }

        $this->db->delete('technicien', ['id_tech' => $id]);
        return $this->noContent();
    }

    private function filterFields(array $d): array
    {
        $allowed = ['id_utilisateur','nom','prenom','email','specialite','telephone',
                    'disponibilite','localisation','image','cin','age','date_naissance','password'];
        return array_intersect_key($d, array_flip($allowed));
    }
}
