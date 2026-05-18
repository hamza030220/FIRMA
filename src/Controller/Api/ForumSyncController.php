<?php

namespace App\Controller\Api;

use App\Entity\Forum\Post;
use App\Repository\User\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/forum')]
class ForumSyncController extends AbstractController
{
    #[Route('/posts', name: 'api_forum_posts_sync', methods: ['POST'])]
    public function syncPost(
        Request $request,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository
    ): JsonResponse {
        if (!$this->isAuthorized($request)) {
            return $this->json([
                'ok' => false,
                'error' => 'Unauthorized.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->json([
                'ok' => false,
                'error' => 'JSON invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($payload)) {
            return $this->json([
                'ok' => false,
                'error' => 'Payload invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $titre = trim((string) ($payload['titre'] ?? $payload['title'] ?? ''));
        $contenu = trim((string) ($payload['contenu'] ?? $payload['content'] ?? ''));
        $categorie = trim((string) ($payload['categorie'] ?? $payload['category'] ?? ''));
        $authorEmail = trim((string) ($payload['author_email'] ?? $payload['authorEmail'] ?? $payload['email'] ?? ''));
        $authorId = (int) ($payload['author_id'] ?? $payload['authorId'] ?? $payload['user_id'] ?? $payload['userId'] ?? 0);

        if ($titre === '' || $contenu === '' || ($authorEmail === '' && $authorId <= 0)) {
            return $this->json([
                'ok' => false,
                'error' => 'Les champs titre, contenu et author_email ou author_id sont obligatoires.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = null;
        if ($authorId > 0) {
            $user = $utilisateurRepository->find($authorId);
        }

        if ($user === null) {
            $user = $utilisateurRepository->findOneBy(['email' => $authorEmail]);
        }
        if ($user === null) {
            return $this->json([
                'ok' => false,
                'error' => sprintf('Utilisateur introuvable pour l\'email %s.', $authorEmail),
            ], Response::HTTP_NOT_FOUND);
        }

        $post = new Post();

        $post
            ->setUtilisateur($user)
            ->setTitre($titre)
            ->setContenu($contenu)
            ->setCategorie($categorie !== '' ? $categorie : null)
            ->setStatut((string) ($payload['statut'] ?? $payload['status'] ?? 'actif'));

        $dateCreation = $this->parseDate((string) ($payload['date_creation'] ?? $payload['dateCreation'] ?? $payload['created_at'] ?? ''));
        if ($dateCreation instanceof \DateTimeInterface) {
            $post->initializeDateCreation($dateCreation);
        }

        $entityManager->persist($post);

        $entityManager->flush();

        return $this->json([
            'ok' => true,
            'action' => 'created',
            'data' => [
                'id' => $post->getId(),
            ],
        ], Response::HTTP_CREATED);
    }

    private function isAuthorized(Request $request): bool
    {
        $expectedToken = trim((string) ($_ENV['FORUM_SYNC_TOKEN'] ?? $_SERVER['FORUM_SYNC_TOKEN'] ?? ''));
        if ($expectedToken !== '') {
            $givenToken = trim((string) $request->headers->get('X-Forum-Sync-Token', ''));

            return $givenToken !== '' && hash_equals($expectedToken, $givenToken);
        }

        return in_array((string) $this->getParameter('kernel.environment'), ['dev', 'test'], true);
    }

    private function parseDate(string $value): ?\DateTimeInterface
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
