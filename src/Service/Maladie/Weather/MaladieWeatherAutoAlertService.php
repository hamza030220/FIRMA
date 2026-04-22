<?php

namespace App\Service\Maladie\Weather;

use App\Entity\User\Utilisateur;
use App\Repository\Maladie\MaladieRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class MaladieWeatherAutoAlertService
{
    private const COOLDOWN_SECONDS = 1800;
    private const WEATHER_CACHE_SECONDS = 600;

    public function __construct(
        private readonly MaladieRepository $maladieRepository,
        private readonly MaladieWeatherRiskService $weatherRiskService,
        private readonly MaladieWeatherAlertMailer $alertMailer,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return array{checked:bool,sent:bool,error:?string,alerts:int,city:?string}
     */
    public function checkAndSendForUser(Utilisateur $user): array
    {
        $city = trim((string) $user->getVille());
        if ($city === '' || !$this->weatherRiskService->isConfigured()) {
            return ['checked' => false, 'sent' => false, 'error' => null, 'alerts' => 0, 'city' => $city !== '' ? $city : null];
        }

        // Short-lived cache: skip entirely if we already checked this city recently.
        // This avoids a synchronous OpenWeatherMap HTTP call on every user page load.
        if ($this->isWeatherCacheFresh($city)) {
            return ['checked' => false, 'sent' => false, 'error' => null, 'alerts' => 0, 'city' => $city];
        }

        try {
            $weather = $this->weatherRiskService->getWeatherByCity($city);
            $this->storeWeatherCache($city);
            $riskAnalyses = [];

            foreach ($this->maladieRepository->findAll() as $maladie) {
                $analysis = $this->weatherRiskService->evaluateFromApiPayload($maladie, $weather);
                if ($this->weatherRiskService->isAlertRiskLevel((string) ($analysis['risk']['level'] ?? 'faible'))) {
                    $riskAnalyses[] = $analysis;
                }
            }

            usort($riskAnalyses, static function (array $left, array $right): int {
                $rank = ['critique' => 3, 'eleve' => 2, 'moyen' => 1, 'faible' => 0];
                return ($rank[$right['risk']['level'] ?? 'faible'] ?? 0) <=> ($rank[$left['risk']['level'] ?? 'faible'] ?? 0);
            });

            if ($riskAnalyses === []) {
                return ['checked' => true, 'sent' => false, 'error' => null, 'alerts' => 0, 'city' => $weather['name'] ?? $city];
            }

            $signature = $this->buildSignature($city, $weather, $riskAnalyses);
            if ($this->isCooldownActive($signature)) {
                return ['checked' => true, 'sent' => false, 'error' => null, 'alerts' => count($riskAnalyses), 'city' => $weather['name'] ?? $city];
            }

            $this->alertMailer->sendRiskAlert($user, $city, $weather, $riskAnalyses);
            $this->storeCooldown($signature);

            return ['checked' => true, 'sent' => true, 'error' => null, 'alerts' => count($riskAnalyses), 'city' => $weather['name'] ?? $city];
        } catch (\Throwable $e) {
            return ['checked' => true, 'sent' => false, 'error' => $e->getMessage(), 'alerts' => 0, 'city' => $city];
        }
    }

    /**
     * @param array<string, mixed> $weather
     * @param array<int, array<string, mixed>> $riskAnalyses
     */
    private function buildSignature(string $city, array $weather, array $riskAnalyses): string
    {
        $items = array_map(static function (array $analysis): array {
            return [
                'maladie' => (string) ($analysis['maladie'] ?? ''),
                'level' => (string) ($analysis['risk']['level'] ?? ''),
            ];
        }, $riskAnalyses);

        return sha1((string) json_encode([
            'city' => mb_strtolower($city),
            'temp' => round((float) ($weather['main']['temp'] ?? 0), 1),
            'humidity' => (int) ($weather['main']['humidity'] ?? 0),
            'rain' => (float) ($weather['rain']['1h'] ?? ($weather['rain']['3h'] ?? 0)),
            'items' => $items,
        ]));
    }

    private function isCooldownActive(string $signature): bool
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            return false;
        }

        $lastSignature = (string) $session->get('maladie_weather_alert_signature', '');
        $lastSentAt = (int) $session->get('maladie_weather_alert_sent_at', 0);

        return $lastSignature === $signature && $lastSentAt > 0 && (time() - $lastSentAt) < self::COOLDOWN_SECONDS;
    }

    private function storeCooldown(string $signature): void
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            return;
        }

        $session->set('maladie_weather_alert_signature', $signature);
        $session->set('maladie_weather_alert_sent_at', time());
    }

    private function isWeatherCacheFresh(string $city): bool
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            return false;
        }
        $lastCity = (string) $session->get('maladie_weather_check_city', '');
        $lastAt   = (int) $session->get('maladie_weather_check_at', 0);
        return $lastCity === mb_strtolower($city) && $lastAt > 0 && (time() - $lastAt) < self::WEATHER_CACHE_SECONDS;
    }

    private function storeWeatherCache(string $city): void
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            return;
        }
        $session->set('maladie_weather_check_city', mb_strtolower($city));
        $session->set('maladie_weather_check_at', time());
    }
}
