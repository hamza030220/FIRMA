<?php

namespace App\Service\Maladie\Diagnostic;

use App\Entity\Maladie\Maladie;
use App\Entity\Maladie\SolutionTraitement;
use App\Repository\Maladie\MaladieRepository;

class MaladieDiagnosticService
{
    public function __construct(
        private readonly MaladieRepository $maladieRepository,
    ) {
    }

    /**
     * @return array<int, array{maladie:Maladie, score:int, traitements:array<int, SolutionTraitement>, matches:array<int, string>}>
     */
    public function diagnose(string $symptomes, ?string $culture = null, ?string $saison = null, int $limit = 3): array
    {
        $tokens = $this->tokenize($symptomes);
        if ($culture) {
            $tokens = array_merge($tokens, $this->tokenize($culture));
        }

        $tokens = array_values(array_unique($tokens));
        if ($tokens === []) {
            return [];
        }

        $results = [];
        foreach ($this->maladieRepository->findAll() as $maladie) {
            $score = 0;
            $matches = [];
            $name = $this->normalizeText((string) $maladie->getNom());
            $sci = $this->normalizeText((string) $maladie->getNomScientifique());
            $desc = $this->normalizeText((string) $maladie->getDescription());
            $symp = $this->normalizeText((string) $maladie->getSymptomes());

            foreach ($tokens as $token) {
                $matched = false;

                if ($symp !== '' && str_contains($symp, $token)) {
                    $score += 2;
                    $matched = true;
                }

                if ($desc !== '' && str_contains($desc, $token)) {
                    $score += 1;
                    $matched = true;
                }

                if (($name !== '' && str_contains($name, $token)) || ($sci !== '' && str_contains($sci, $token))) {
                    $score += 1;
                    $matched = true;
                }

                if ($matched) {
                    $matches[] = $token;
                }
            }

            if ($saison && $maladie->getSaisonFrequente()) {
                $saisonNorm = $this->normalizeText($saison);
                $maladieSaison = $this->normalizeText((string) $maladie->getSaisonFrequente());
                if ($saisonNorm !== '' && $maladieSaison === $saisonNorm) {
                    $score += 2;
                }
            }

            if ($score <= 0) {
                continue;
            }

            $traitements = $maladie->getSolutionTraitements()->toArray();
            usort($traitements, function (SolutionTraitement $left, SolutionTraitement $right): int {
                return $this->computeTreatmentScore($right) <=> $this->computeTreatmentScore($left);
            });

            $results[] = [
                'maladie' => $maladie,
                'score' => $score,
                'matches' => array_values(array_unique($matches)),
                'traitements' => $traitements,
            ];
        }

        usort($results, static function (array $left, array $right): int {
            return $right['score'] <=> $left['score'];
        });

        return array_slice($results, 0, max(1, $limit));
    }

    private function computeTreatmentScore(SolutionTraitement $traitement): int
    {
        return (int) (($traitement->getFeedbackPositive() * 3)
            + $traitement->getUsageCount()
            - ($traitement->getFeedbackNegative() * 2));
    }

    private function normalizeText(string $text): string
    {
        $text = trim(mb_strtolower($text));
        if ($text === '') {
            return '';
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($ascii !== false) {
            $text = $ascii;
        }

        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        return trim($text);
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $normalized = $this->normalizeText($text);
        if ($normalized === '') {
            return [];
        }

        $parts = explode(' ', $normalized);
        $tokens = [];

        foreach ($parts as $part) {
            if (mb_strlen($part) >= 3) {
                $tokens[] = $part;
            }
        }

        return $tokens;
    }
}
