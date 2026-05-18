<?php

namespace App\Controller\User;

use App\Service\Forum\ForumTranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/forum/api')]
#[IsGranted('ROLE_USER')]
class ForumTranslationController extends AbstractController
{
    #[Route('/translate', name: 'user_forum_translate', methods: ['POST'])]
    public function translate(Request $request, ForumTranslationService $translationService): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (JsonException $exception) {
            return $this->json([
                'ok' => false,
                'error' => 'Le corps JSON est invalide.',
            ], 400);
        }

        $text = (string) ($data['text'] ?? '');
        $targetLanguage = (string) ($data['targetLanguage'] ?? $data['target_lang'] ?? 'FR');
        $sourceLanguage = isset($data['sourceLanguage']) ? (string) $data['sourceLanguage'] : (isset($data['source_lang']) ? (string) $data['source_lang'] : null);

        try {
            $result = $translationService->translate($text, $targetLanguage, $sourceLanguage);
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
