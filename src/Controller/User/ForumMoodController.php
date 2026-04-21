<?php

namespace App\Controller\User;

use App\Entity\Forum\Post;
use App\Repository\Forum\PostRepository;
use App\Service\Forum\ForumMoodAnalysisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/forum/api')]
#[IsGranted('ROLE_USER')]
class ForumMoodController extends AbstractController
{
    #[Route('/mood/analyze', name: 'user_forum_mood_analyze', methods: ['POST'])]
    public function analyze(Request $request, ForumMoodAnalysisService $moodAnalysisService, PostRepository $postRepository): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (JsonException) {
            $data = $request->request->all();
        }

        $postId = isset($data['postId']) ? (int) $data['postId'] : 0;
        $text = trim((string) ($data['text'] ?? $data['contenu'] ?? $data['content'] ?? ''));

        if ($postId > 0) {
            $post = $postRepository->findOneForForum($postId);
            if (!$post instanceof Post) {
                return $this->json([
                    'ok' => false,
                    'error' => 'Post introuvable.',
                ], 404);
            }

            $text = trim((string) $post->getTitre() . ' ' . (string) $post->getContenu());
        }

        if ($text === '') {
            return $this->json([
                'ok' => false,
                'error' => 'Le texte du post est obligatoire.',
            ], 422);
        }

        try {
            $result = $moodAnalysisService->analyze($text);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'ok' => false,
                'error' => $exception->getMessage(),
            ], 422);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'ok' => false,
                'error' => $exception->getMessage(),
            ], 502);
        }

        return $this->json([
            'ok' => true,
            'data' => $result,
        ]);
    }
}
