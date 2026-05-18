<?php

namespace App\Service\Maladie\Weather;

use App\Entity\Maladie\Maladie;
use App\Entity\Maladie\SolutionTraitement;
use App\Repository\Maladie\SolutionTraitementRepository;

class MaladieWeatherRiskService
{
    public function __construct(
        private readonly OpenWeatherMapClient $weatherClient,
        private readonly SolutionTraitementRepository $traitementRepository,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->weatherClient->isConfigured();
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluate(Maladie $maladie, float $latitude, float $longitude): array
    {
        $weather = $this->weatherClient->getCurrentWeather($latitude, $longitude);
        return $this->buildEvaluation($maladie, $weather, [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluateByCity(Maladie $maladie, string $city): array
    {
        $weather = $this->weatherClient->getCurrentWeatherByCity($city);

        return $this->buildEvaluation($maladie, $weather, [
            'latitude' => isset($weather['coord']['lat']) ? (float) $weather['coord']['lat'] : null,
            'longitude' => isset($weather['coord']['lon']) ? (float) $weather['coord']['lon'] : null,
        ]);
    }

    /**
     * @param array<string, mixed> $weather
     * @return array<string, mixed>
     */
    public function evaluateFromApiPayload(Maladie $maladie, array $weather): array
    {
        return $this->buildEvaluation($maladie, $weather, [
            'latitude' => isset($weather['coord']['lat']) ? (float) $weather['coord']['lat'] : null,
            'longitude' => isset($weather['coord']['lon']) ? (float) $weather['coord']['lon'] : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getWeatherByCity(string $city): array
    {
        return $this->weatherClient->getCurrentWeatherByCity($city);
    }

    /**
     * @param array<string, mixed> $weather
     * @param array{latitude:?float,longitude:?float} $location
     * @return array<string, mixed>
     */
    private function buildEvaluation(Maladie $maladie, array $weather, array $location): array
    {
        $temperature = isset($weather['main']['temp']) ? (float) $weather['main']['temp'] : null;
        $humidity = isset($weather['main']['humidity']) ? (int) $weather['main']['humidity'] : null;
        $rain = isset($weather['rain']['1h']) ? (float) $weather['rain']['1h'] : (isset($weather['rain']['3h']) ? (float) $weather['rain']['3h'] : 0.0);
        $wind = isset($weather['wind']['speed']) ? (float) $weather['wind']['speed'] : null;
        $condition = $weather['weather'][0]['description'] ?? 'Condition indisponible';
        $risk = $this->matchRisk($maladie, $temperature, $humidity, $rain);
        $traitement = $this->findSuggestedTreatment($maladie);

        return [
            'configured' => true,
            'maladie' => $maladie->getNom(),
            'location' => $location,
            'weather' => [
                'temperature' => $temperature,
                'humidity' => $humidity,
                'rain' => $rain,
                'wind' => $wind,
                'condition' => $condition,
                'city' => $weather['name'] ?? null,
            ],
            'risk' => $risk,
            'thresholds' => [
                'tempMin' => $maladie->getTempMin(),
                'tempMax' => $maladie->getTempMax(),
                'humiditeMin' => $maladie->getHumiditeMin(),
            ],
            'suggestedTreatment' => $traitement ? $this->normalizeTreatment($traitement) : null,
        ];
    }

    /**
     * @param array<string, mixed> $weather
     * @return array<string, mixed>
     */
    public function evaluateFromWeatherData(Maladie $maladie, array $weather, float $latitude = 0.0, float $longitude = 0.0): array
    {
        $temperature = isset($weather['temperature']) ? (float) $weather['temperature'] : null;
        $humidity = isset($weather['humidity']) ? (int) $weather['humidity'] : null;
        $rain = isset($weather['rain']) ? (float) $weather['rain'] : 0.0;
        $wind = isset($weather['wind']) ? (float) $weather['wind'] : null;
        $condition = isset($weather['condition']) ? (string) $weather['condition'] : 'Simulation manuelle';

        $risk = $this->matchRisk($maladie, $temperature, $humidity, $rain);
        $traitement = $this->findSuggestedTreatment($maladie);

        return [
            'configured' => true,
            'simulation' => true,
            'maladie' => $maladie->getNom(),
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
            'weather' => [
                'temperature' => $temperature,
                'humidity' => $humidity,
                'rain' => $rain,
                'wind' => $wind,
                'condition' => $condition,
                'city' => 'Mode test',
            ],
            'risk' => $risk,
            'suggestedTreatment' => $traitement ? $this->normalizeTreatment($traitement) : null,
        ];
    }

    /**
     * @return array{level:string,label:string,message:string,reasons:string[]}
     */
    private function matchRisk(Maladie $maladie, ?float $temperature, ?int $humidity, float $rain): array
    {
        $tempMin = $maladie->getTempMin();
        $tempMax = $maladie->getTempMax();
        $humiditeMin = $maladie->getHumiditeMin();
        $name = mb_strtolower((string) $maladie->getNom());
        $scientificName = mb_strtolower((string) $maladie->getNomScientifique());
        $fullName = trim($name . ' ' . $scientificName);
        $reasons = [];
        $level = 'faible';
        $label = 'Faible';
        $message = 'Les conditions meteo actuelles ne suggerent pas un risque notable.';

        if ($tempMin !== null || $tempMax !== null || $humiditeMin !== null) {
            $temperatureMatches = $temperature !== null
                && ($tempMin === null || $temperature >= $tempMin)
                && ($tempMax === null || $temperature <= $tempMax);

            $humidityMatches = $humiditeMin !== null && $humidity !== null && $humidity >= $humiditeMin;

            if ($temperatureMatches) {
                $reasons[] = 'Temperature actuelle dans la plage de risque';
            }

            if ($humidityMatches) {
                $reasons[] = 'Humidite actuelle au dessus du seuil de risque';
            }

            if ($rain > 0) {
                $reasons[] = 'Pluie recente detectee';
            }

            if ($temperatureMatches && $humidityMatches && $rain > 0) {
                $level = 'critique';
                $label = 'Critique';
                $message = 'Les seuils definis pour cette maladie sont reunis avec humidite et pluie. Le risque est critique.';
            } elseif ($temperatureMatches && $humidityMatches) {
                $level = 'eleve';
                $label = 'Eleve';
                $message = 'La temperature et l humidite actuelles correspondent aux seuils de risque de cette maladie.';
            } elseif ($temperatureMatches || $humidityMatches) {
                $level = 'moyen';
                $label = 'Moyen';
                $message = 'Une partie des seuils de risque de cette maladie est atteinte. Une surveillance preventive est conseillee.';
            }

            return compact('level', 'label', 'message', 'reasons');
        }

        if (str_contains($fullName, 'mildiou')) {
            if ($humidity !== null && $humidity >= 80) {
                $reasons[] = 'Humidite elevee';
            }
            if ($temperature !== null && $temperature >= 15 && $temperature <= 25) {
                $reasons[] = 'Temperature favorable au mildiou';
            }
            if ($rain > 0) {
                $reasons[] = 'Presence de pluie recente';
            }

            if (count($reasons) >= 3) {
                $level = 'critique';
                $label = 'Critique';
                $message = 'Conditions tres favorables au developpement du mildiou. Une intervention rapide est recommandee.';
            } elseif (count($reasons) === 2) {
                $level = 'eleve';
                $label = 'Eleve';
                $message = 'Le risque de mildiou est eleve selon la meteo actuelle.';
            } elseif (count($reasons) === 1) {
                $level = 'moyen';
                $label = 'Moyen';
                $message = 'Une condition favorable au mildiou est detectee. Surveillez la parcelle.';
            }

            return compact('level', 'label', 'message', 'reasons');
        }

        if ($humidity !== null && $humidity >= 85 && $temperature !== null && $temperature >= 18 && $temperature <= 28) {
            $level = 'eleve';
            $label = 'Eleve';
            $message = 'Le niveau d humidite et la temperature peuvent favoriser des maladies fongiques.';
            $reasons = ['Humidite tres elevee', 'Temperature moderee a chaude'];
        } elseif ($humidity !== null && $humidity >= 75) {
            $level = 'moyen';
            $label = 'Moyen';
            $message = 'L humidite actuelle justifie une vigilance preventive.';
            $reasons = ['Humidite soutenue'];
        }

        return compact('level', 'label', 'message', 'reasons');
    }

    public function isAlertRiskLevel(string $level): bool
    {
        return in_array($level, ['eleve', 'critique'], true);
    }

    private function findSuggestedTreatment(Maladie $maladie): ?SolutionTraitement
    {
        $traitements = $this->traitementRepository->findByMaladieId((int) $maladie->getId());

        if ($traitements === []) {
            return null;
        }

        usort($traitements, static function (SolutionTraitement $left, SolutionTraitement $right): int {
            $leftScore = ($left->getFeedbackPositive() * 3) + $left->getUsageCount() - ($left->getFeedbackNegative() * 2);
            $rightScore = ($right->getFeedbackPositive() * 3) + $right->getUsageCount() - ($right->getFeedbackNegative() * 2);

            return $rightScore <=> $leftScore;
        });

        return $traitements[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeTreatment(SolutionTraitement $traitement): array
    {
        return [
            'id' => $traitement->getId(),
            'titre' => $traitement->getTitre(),
            'solution' => $traitement->getSolution(),
            'dureeTraitement' => $traitement->getDureeTraitement(),
            'usageCount' => $traitement->getUsageCount(),
            'feedbackPositive' => $traitement->getFeedbackPositive(),
            'feedbackNegative' => $traitement->getFeedbackNegative(),
        ];
    }
}
