<?php

namespace App\Service\Forum;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ForumMoodAnalysisService
{
    private const MAX_TEXT_BYTES = 120000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $huggingfaceToken,
        private readonly string $huggingfaceBaseUri = 'https://router.huggingface.co/hf-inference',
        private readonly string $huggingfaceModel = 'nlptown/bert-base-multilingual-uncased-sentiment',
    ) {
    }

    /**
     * @return array{
     *     mood: 'positif'|'negatif'|'neutre',
     *     score: float,
     *     label: string,
     *     scores: list<array{label: string, score: float}>,
     *     text: string
     * }
     */
    public function analyze(string $text): array
    {
        if (trim($this->huggingfaceToken) === '') {
            throw new \RuntimeException("Le jeton Hugging Face est manquant. Ajoutez HUGGINGFACE_API_TOKEN dans .env.local.");
        }

        $text = trim($text);
        if ($text === '') {
            throw new \InvalidArgumentException('Le texte du post est obligatoire.');
        }

        if (strlen($text) > self::MAX_TEXT_BYTES) {
            throw new \InvalidArgumentException('Le texte du post depasse la taille maximale autorisee.');
        }

        try {
            $response = $this->httpClient->request('POST', rtrim($this->huggingfaceBaseUri, '/') . '/models/' . ltrim($this->huggingfaceModel, '/'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->huggingfaceToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => $text,
                    'options' => [
                        'wait_for_model' => true,
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $rawPayload = $response->getContent(false);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Impossible de joindre Hugging Face: ' . $exception->getMessage(), 0, $exception);
        }

        $payload = json_decode($rawPayload, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        if ($statusCode >= 400) {
            $message = $payload['error'] ?? $payload['message'] ?? trim($rawPayload);
            if ($message === '') {
                $message = 'Hugging Face a repondu avec le code HTTP ' . $statusCode . '.';
            }

            throw new \RuntimeException(is_string($message) ? $message : 'Erreur inattendue lors de l\'analyse du sentiment.');
        }

        $result = $this->normalizeResponse($payload);
        if ($result === []) {
            throw new \RuntimeException('Reponse Hugging Face invalide.');
        }

        $best = $result[0];
        $mood = $this->mapMood($best['label']);

        return [
            'mood' => $mood,
            'score' => $best['score'],
            'label' => $best['label'],
            'scores' => $result,
            'text' => $text,
        ];
    }

    /**
     * @param mixed $payload
     * @return list<array{label: string, score: float}>
     */
    private function normalizeResponse(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $items = [];

        if (isset($payload['label'], $payload['score'])) {
            return [[
                'label' => (string) $payload['label'],
                'score' => (float) $payload['score'],
            ]];
        }

        if (isset($payload[0]) && is_array($payload[0]) && isset($payload[0]['label'], $payload[0]['score']) && count($payload) === 1) {
            return [[
                'label' => (string) $payload[0]['label'],
                'score' => (float) $payload[0]['score'],
            ]];
        }

        if (isset($payload[0]) && is_array($payload[0]) && isset($payload[0][0]) && is_array($payload[0][0]) && isset($payload[0][0]['label'], $payload[0][0]['score'])) {
            $payload = $payload[0];
        }

        foreach ($payload as $item) {
            if (is_array($item) && isset($item[0]) && is_array($item[0]) && isset($item[0]['label'], $item[0]['score'])) {
                foreach ($item as $nestedItem) {
                    if (!is_array($nestedItem) || !isset($nestedItem['label'], $nestedItem['score'])) {
                        continue;
                    }

                    $items[] = [
                        'label' => (string) $nestedItem['label'],
                        'score' => (float) $nestedItem['score'],
                    ];
                }

                continue;
            }

            if (!is_array($item) || !isset($item['label'], $item['score'])) {
                continue;
            }

            $items[] = [
                'label' => (string) $item['label'],
                'score' => (float) $item['score'],
            ];
        }

        usort($items, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return $items;
    }

    private function mapMood(string $label): string
    {
        $normalized = strtolower(trim($label));

        if (preg_match('/\b([1-5])\b/', $normalized, $matches) === 1) {
            return match ((int) $matches[1]) {
                1, 2 => 'negatif',
                3 => 'neutre',
                4, 5 => 'positif',
            };
        }

        if (str_contains($normalized, 'star')) {
            if (str_contains($normalized, '1')) {
                return 'negatif';
            }

            if (str_contains($normalized, '2')) {
                return 'negatif';
            }

            if (str_contains($normalized, '3')) {
                return 'neutre';
            }

            if (str_contains($normalized, '4') || str_contains($normalized, '5')) {
                return 'positif';
            }
        }

        return match (true) {
            str_contains($normalized, 'pos') || str_contains($normalized, 'positive') || str_contains($normalized, 'label_2') => 'positif',
            str_contains($normalized, 'neg') || str_contains($normalized, 'negative') || str_contains($normalized, 'label_0') => 'negatif',
            default => 'neutre',
        };
    }
}
