<?php

namespace App\Service\Forum;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ForumBadWordsService
{
    private const DEFAULT_ENDPOINT = 'https://api.sapling.ai/api/v1/profanity';

    private const LOCAL_WORDS = [
        'culot',
        'merde',
        'merdre',
        'connard',
        'putain',
        'conard',
        'encule',
        'encule',
        'salope',
        'pute',
        'shit',
        'fuck',
        'bitch',
        'asshole',
        'dick',
        'bastard',
        'idiot',
        'stupid',
    ];

    private const LOCAL_PHRASES = [
        'va te faire foutre',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return array{blocked: bool, matches: list<string>, normalizedText: string, provider: string}
     */
    public function scan(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [
                'blocked' => false,
                'matches' => [],
                'normalizedText' => '',
                'provider' => 'sapling+local',
            ];
        }

        $localMatches = array_values(array_unique(array_merge(
            $this->findLocalMatches($text),
            $this->findLocalPhraseMatches($text)
        )));

        $key = $this->resolveApiKey();
        if ($key === '') {
            return [
                'blocked' => $localMatches !== [],
                'matches' => $localMatches,
                'normalizedText' => $this->normalizeText($text),
                'provider' => 'local',
            ];
        }

        $endpoint = $this->resolveEndpoint();

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'json' => [
                    'key' => $key,
                    'text' => $text,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode >= 400) {
                $message = $data['msg'] ?? $data['message'] ?? ('Sapling a repondu avec le code HTTP ' . $statusCode . '.');
                throw new \RuntimeException(is_string($message) ? $message : 'Erreur inattendue lors de la moderation.');
            }

            $scan = $this->normalizeSaplingResponse($text, $data);

            if ($localMatches !== []) {
                $scan['matches'] = array_values(array_unique(array_merge($scan['matches'], $localMatches)));
                $scan['blocked'] = true;
                $scan['provider'] = 'sapling+local';
            }

            return $scan;
        } catch (\Throwable) {
            return [
                'blocked' => $localMatches !== [],
                'matches' => $localMatches,
                'normalizedText' => $this->normalizeText($text),
                'provider' => 'local-fallback',
            ];
        }
    }

    /**
     * @return array{ok: bool, matches: list<string>, reason: string|null, provider: string}
     */
    public function check(string $text): array
    {
        $scan = $this->scan($text);

        return [
            'ok' => !$scan['blocked'],
            'matches' => $scan['matches'],
            'reason' => $scan['blocked'] ? 'Le contenu contient un mot interdit.' : null,
            'provider' => $scan['provider'],
        ];
    }

    /**
     * @return array{maskedText: string, matches: list<string>, provider: string}
     */
    public function mask(string $text): array
    {
        $scan = $this->scan($text);
        $maskedText = $text;

        foreach ($scan['matches'] as $word) {
            if (str_contains($word, ' ')) {
                $maskedText = $this->maskPhrase($maskedText, $word);
                continue;
            }

            $maskedText = $this->maskWord($maskedText, $word);
        }

        return [
            'maskedText' => $maskedText,
            'matches' => $scan['matches'],
            'provider' => $scan['provider'],
        ];
    }

    private function resolveApiKey(): string
    {
        $key = getenv('SAPLING_API_KEY');
        if (!is_string($key) || trim($key) === '') {
            $key = $_ENV['SAPLING_API_KEY'] ?? $_SERVER['SAPLING_API_KEY'] ?? '';
        }

        return trim((string) $key);
    }

    private function resolveEndpoint(): string
    {
        $endpoint = getenv('SAPLING_PROFANITY_BASE_URI');
        if (!is_string($endpoint) || trim($endpoint) === '') {
            $endpoint = $_ENV['SAPLING_PROFANITY_BASE_URI'] ?? $_SERVER['SAPLING_PROFANITY_BASE_URI'] ?? self::DEFAULT_ENDPOINT;
        }

        return trim((string) $endpoint) !== '' ? trim((string) $endpoint) : self::DEFAULT_ENDPOINT;
    }

    public function isStandaloneProfanity(string $text): bool
    {
        $normalizedText = $this->normalizeText($text);
        if ($normalizedText === '') {
            return false;
        }

        foreach (self::LOCAL_PHRASES as $phrase) {
            if ($this->normalizeText($phrase) === $normalizedText) {
                return true;
            }
        }

        $tokens = explode(' ', $normalizedText);
        if ($tokens === []) {
            return false;
        }

        $normalizedWords = array_flip(array_map([$this, 'normalizeText'], self::LOCAL_WORDS));
        if ($normalizedWords === []) {
            return false;
        }

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            if (!isset($normalizedWords[$token])) {
                return false;
            }
        }

        return true;
    }

    public function isFullyMaskedProfanity(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return false;
        }

        $hasMaskedToken = false;
        foreach ($parts as $part) {
            if (trim($part) === '') {
                continue;
            }

            if (preg_match('/^[\p{P}\p{S}]+$/u', $part) === 1) {
                continue;
            }

            if (preg_match('/^[\p{L}]\*+$/u', $part) === 1) {
                $hasMaskedToken = true;
                continue;
            }

            if (preg_match('/^\*+$/u', $part) === 1) {
                $hasMaskedToken = true;
                continue;
            }

            return false;
        }

        return $hasMaskedToken;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{blocked: bool, matches: list<string>, normalizedText: string, provider: string}
     */
    private function normalizeSaplingResponse(string $text, array $data): array
    {
        $tokens = isset($data['toks']) && is_array($data['toks']) ? $data['toks'] : [];
        $labels = isset($data['labels']) && is_array($data['labels']) ? $data['labels'] : [];
        $matches = [];

        foreach ($tokens as $index => $token) {
            if (!is_scalar($token)) {
                continue;
            }

            $label = $labels[$index] ?? 0;
            if ((int) $label === 1) {
                $matches[] = trim((string) $token, " \t\n\r\0\x0B.,;:!?()[]{}\"'");
            }
        }

        $matches = array_values(array_unique(array_filter($matches, static fn (string $word): bool => $word !== '')));

        return [
            'blocked' => $matches !== [],
            'matches' => $matches,
            'normalizedText' => trim($text),
            'provider' => 'sapling',
        ];
    }

    /**
     * @return list<string>
     */
    private function findLocalMatches(string $text): array
    {
        $normalizedText = $this->normalizeText($text);
        $matches = [];

        foreach (self::LOCAL_WORDS as $word) {
            $normalizedWord = $this->normalizeText($word);
            if ($normalizedWord === '') {
                continue;
            }

            if ($this->containsWord($normalizedText, $normalizedWord)) {
                $matches[] = $word;
            }
        }

        return array_values(array_unique($matches));
    }

    /**
     * @return list<string>
     */
    private function findLocalPhraseMatches(string $text): array
    {
        $normalizedText = $this->normalizeText($text);
        $matches = [];

        foreach (self::LOCAL_PHRASES as $phrase) {
            $normalizedPhrase = $this->normalizeText($phrase);
            if ($normalizedPhrase === '') {
                continue;
            }

            if ($this->containsWord($normalizedText, $normalizedPhrase)) {
                $matches[] = $phrase;
            }
        }

        return array_values(array_unique($matches));
    }

    private function normalizeText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($ascii) && $ascii !== '') {
            $text = $ascii;
        }

        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function containsWord(string $haystack, string $needle): bool
    {
        if ($haystack === '' || $needle === '') {
            return false;
        }

        return (bool) preg_match('/(?:^|\s)' . preg_quote($needle, '/') . '(?:\s|$)/u', ' ' . $haystack . ' ');
    }

    private function maskWord(string $text, string $word): string
    {
        $word = trim($word);
        if ($word === '') {
            return $text;
        }

        return (string) preg_replace_callback(
            '/\b' . preg_quote($word, '/') . '\b/iu',
            static function (array $matches): string {
                $matchedWord = $matches[0] ?? '';
                $letters = preg_split('//u', $matchedWord, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                if ($letters === []) {
                    return $matchedWord;
                }

                $firstLetter = array_shift($letters);
                return $firstLetter . str_repeat('*', count($letters));
            },
            $text
        );
    }

    private function maskPhrase(string $text, string $phrase): string
    {
        $phrase = trim($phrase);
        if ($phrase === '') {
            return $text;
        }

        return (string) preg_replace_callback(
            '/\b' . preg_quote($phrase, '/') . '\b/iu',
            static function (array $matches): string {
                $matchedPhrase = trim((string) ($matches[0] ?? ''));
                if ($matchedPhrase === '') {
                    return $matchedPhrase;
                }

                $maskedTokens = [];
                foreach (preg_split('/\s+/', $matchedPhrase, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
                    $letters = preg_split('//u', $token, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    if ($letters === []) {
                        $maskedTokens[] = $token;
                        continue;
                    }

                    $firstLetter = array_shift($letters);
                    $maskedTokens[] = $firstLetter . str_repeat('*', count($letters));
                }

                return implode(' ', $maskedTokens);
            },
            $text
        );
    }
}
