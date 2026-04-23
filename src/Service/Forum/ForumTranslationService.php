<?php

namespace App\Service\Forum;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ForumTranslationService
{
    private const MAX_TEXT_BYTES = 131072;

    /**
     * DeepL supports many target languages. We expose a curated set for the forum UI.
     */
    private const SUPPORTED_TARGET_LANGUAGES = [
        'FR',
        'EN-GB',
        'EN-US',
        'DE',
        'ES',
        'IT',
        'NL',
        'PT-BR',
        'PT-PT',
        'PL',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $deeplAuthKey,
        private readonly string $deeplBaseUri = 'https://api-free.deepl.com',
    ) {
    }

    /**
     * @return array{text: string, detectedSourceLanguage: ?string, targetLanguage: string}
     */
    public function translate(string $text, string $targetLanguage, ?string $sourceLanguage = null): array
    {
        if (trim($this->deeplAuthKey) === '') {
            throw new \RuntimeException('La clé DeepL est manquante. Ajoutez DEEPL_AUTH_KEY dans .env.local.');
        }

        if (trim($this->deeplBaseUri) === '') {
            throw new \RuntimeException("L'URL DeepL est manquante.");
        }

        $text = trim($text);
        if ($text === '') {
            throw new \InvalidArgumentException('Le texte à traduire est obligatoire.');
        }

        if (strlen($text) > self::MAX_TEXT_BYTES) {
            throw new \InvalidArgumentException('Le texte dépasse la taille maximale autorisée.');
        }

        $targetLanguage = $this->normalizeLanguageCode($targetLanguage);
        if (!in_array($targetLanguage, self::SUPPORTED_TARGET_LANGUAGES, true)) {
            throw new \InvalidArgumentException("La langue cible choisie n'est pas prise en charge.");
        }

        $payload = [
            'text' => [$text],
            'target_lang' => $targetLanguage,
        ];

        if ($sourceLanguage !== null && trim($sourceLanguage) !== '') {
            $payload['source_lang'] = $this->normalizeLanguageCode($sourceLanguage);
        }

        $response = $this->httpClient->request('POST', rtrim($this->deeplBaseUri, '/') . '/v2/translate', [
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $this->deeplAuthKey,
            ],
            'json' => $payload,
        ]);

        $statusCode = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($statusCode >= 400) {
            $message = $data['message'] ?? $data['error'] ?? ('DeepL a répondu avec le code HTTP ' . $statusCode . '.');
            throw new \RuntimeException(is_string($message) ? $message : 'Erreur inattendue lors de la traduction.');
        }

        $translation = $data['translations'][0] ?? null;
        if (!is_array($translation) || !isset($translation['text'])) {
            throw new \RuntimeException('Réponse de traduction invalide.');
        }

        return [
            'text' => (string) $translation['text'],
            'detectedSourceLanguage' => isset($translation['detected_source_language']) ? (string) $translation['detected_source_language'] : null,
            'targetLanguage' => $targetLanguage,
        ];
    }

    /**
     * @return list<string>
     */
    public static function getSupportedTargetLanguages(): array
    {
        return self::SUPPORTED_TARGET_LANGUAGES;
    }

    private function normalizeLanguageCode(string $languageCode): string
    {
        return strtoupper(trim($languageCode));
    }
}
