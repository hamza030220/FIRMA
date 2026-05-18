<?php

namespace App\Controller\User;

use App\Service\Forum\ForumBadWordsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/forum/api')]
#[IsGranted('ROLE_USER')]
class ForumBadWordsController extends AbstractController
{
    #[Route('/bad-words/check', name: 'user_forum_bad_words_check', methods: ['POST'])]
    public function check(Request $request, ForumBadWordsService $badWordsService): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (JsonException) {
            $data = $request->request->all();
        }

        $text = trim((string) ($data['text'] ?? $data['contenu'] ?? $data['content'] ?? ''));
        if ($text === '') {
            return $this->json([
                'ok' => true,
                'data' => [
                    'blocked' => false,
                    'matches' => [],
                    'reason' => null,
                ],
            ]);
        }

        return $this->json([
            'ok' => true,
            'data' => $badWordsService->check($text),
        ]);
    }
}
