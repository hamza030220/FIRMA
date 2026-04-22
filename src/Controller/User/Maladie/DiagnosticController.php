<?php

namespace App\Controller\User\Maladie;

use App\Service\Maladie\Diagnostic\MaladieDiagnosticService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/user/maladies')]
#[IsGranted('ROLE_USER')]
class DiagnosticController extends AbstractController
{
    private const ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];
    private const MAX_IMAGE_SIZE_BYTES = 5_000_000;

    private const ALLOWED_SAISONS = [
        '',
        'Printemps',
        'Ete',
        'Été',
        'Automne',
        'Hiver',
        'Printemps-Ete',
        'Printemps-Été',
        'Printemps-Automne',
    ];

    #[Route('/diagnostic', name: 'user_maladie_diagnostic', methods: ['GET', 'POST'])]
    public function diagnostic(Request $request, MaladieDiagnosticService $diagnosticService): Response
    {
        $errors = [];
        $symptomes = '';
        $culture = '';
        $saison = '';
        $results = [];

        if ($request->isMethod('POST')) {
            $symptomes = trim((string) $request->request->get('symptomes', ''));
            $culture = trim((string) $request->request->get('culture', ''));
            $saison = trim((string) $request->request->get('saison', ''));

            if ($symptomes === '' || mb_strlen($symptomes) < 10) {
                $errors['symptomes'] = 'Veuillez decrire les symptomes (au moins 10 caracteres).';
            }

            if (!in_array($saison, self::ALLOWED_SAISONS, true)) {
                $errors['saison'] = 'La saison selectionnee est invalide.';
            }

            if ($errors === []) {
                $results = $diagnosticService->diagnose($symptomes, $culture ?: null, $saison ?: null, 50);
            }
        }

        return $this->render('user/maladie/diagnostic.html.twig', [
            'errors' => $errors,
            'symptomes' => $symptomes,
            'culture' => $culture,
            'saison' => $saison,
            'results' => $results,
        ]);
    }

    #[Route('/diagnostic-photo', name: 'user_maladie_diagnostic_photo', methods: ['GET', 'POST'])]
    public function diagnosticPhoto(Request $request, HttpClientInterface $httpClient): Response
    {
        $errors = [];
        $diagnosis = [];
        $rawResponse = null;
        $plantName = null;
        $imageUrl = null;
        $analysisImage = null;

        if ($request->isMethod('POST')) {
            $uploaded = $request->files->get('photo');

            if (!$uploaded instanceof UploadedFile) {
                $errors[] = 'Veuillez selectionner une photo.';
            } elseif (!$uploaded->isValid()) {
                $errors[] = 'Le telechargement a echoue. Merci de reessayer.';
            } elseif (!in_array($uploaded->getMimeType(), self::ALLOWED_IMAGE_MIME_TYPES, true)) {
                $errors[] = 'Format non supporte. Utilisez JPG, PNG ou WEBP.';
            } elseif ($uploaded->getSize() > self::MAX_IMAGE_SIZE_BYTES) {
                $errors[] = 'La photo est trop grande (5 Mo max).';
            }

            $apiUrl = (string) $this->getParameter('app.plant_api_url');
            $apiKey = (string) $this->getParameter('app.plant_api_key');

            if ($errors === [] && ($apiKey === '' || $apiKey === 'CHANGE_ME')) {
                $errors[] = 'Veuillez configurer la cle API externe.';
            }

            if ($errors === [] && $apiUrl === '') {
                $errors[] = 'Veuillez configurer l\'URL de l\'API externe.';
            }

            if ($errors === []) {
                try {
                    $imageContents = file_get_contents($uploaded->getPathname());
                    if ($imageContents === false) {
                        throw new \RuntimeException('Image read failed');
                    }

                    $analysisImage = 'data:' . (string) $uploaded->getMimeType() . ';base64,' . base64_encode($imageContents);

                    $requestOptions = [
                        'headers' => [
                            'Api-Key' => $apiKey,
                            'Content-Type' => 'application/json',
                        ],
                        'json' => [
                            'images' => [
                                base64_encode($imageContents),
                            ],
                            'language' => 'fr',
                            'organs' => [
                                'leaf',
                            ],
                            'disease_details' => [
                                'treatment',
                            ],
                        ],
                        'timeout' => 25,
                    ];

                    $response = $httpClient->request('POST', $apiUrl, $requestOptions);
                    $statusCode = $response->getStatusCode();
                    $content = $response->getContent(false);

                    $payload = json_decode($content, true);
                    $rawResponse = is_array($payload) ? $payload : $content;

                    if ($statusCode >= 400) {
                        $errors[] = sprintf('Erreur API (%d).', $statusCode);
                    }

                    if (is_array($payload)) {
                        $imageUrl = $payload['images'][0]['url']
                            ?? $payload['image']['url']
                            ?? $payload['image_url']
                            ?? null;

                        $plantName = $payload['result']['classification']['suggestions'][0]['name']
                            ?? $payload['classification']['suggestions'][0]['name']
                            ?? $payload['result']['plant']['name']
                            ?? $payload['plant']['name']
                            ?? $payload['plant_name']
                            ?? $payload['plant']['scientific_name']
                            ?? null;

                        if (isset($payload['error'])) {
                            $errors[] = (string) $payload['error'];
                        }

                        if (isset($payload['message']) && is_string($payload['message'])) {
                            $errors[] = $payload['message'];
                        }

                        $suggestions = $payload['result']['disease']['suggestions']
                            ?? $payload['health_assessment']['diseases']
                            ?? $payload['result']['diseases']
                            ?? $payload['diseases']
                            ?? $payload['suggestions']
                            ?? $payload['results']
                            ?? $payload['health_assessment']['disease']
                            ?? [];

                        if (is_array($suggestions)) {
                            foreach ($suggestions as $item) {
                                if (!is_array($item)) {
                                    continue;
                                }

                                $diseaseDetails = $item['disease_details'] ?? $item['details'] ?? null;

                                $diagnosis[] = [
                                    'plant' => $plantName,
                                    'name' => $item['name']
                                        ?? $item['label']
                                        ?? $item['common_name']
                                        ?? ($diseaseDetails['local_name'] ?? null)
                                        ?? $item['scientificName']
                                        ?? $item['species']
                                        ?? 'Resultat',
                                    'score' => $item['probability']
                                        ?? $item['score']
                                        ?? $item['confidence']
                                        ?? null,
                                    'treatment' => $this->formatTreatment($diseaseDetails['treatment'] ?? $item['treatment'] ?? null),
                                ];
                            }
                        }
                    } elseif ($content !== '') {
                        $errors[] = $content;
                    }
                } catch (\Throwable $exception) {
                    $errors[] = 'Erreur lors de l\'appel API externe. Verifiez la cle ou l\'URL.';
                }
            }
        }

        return $this->render('user/maladie/diagnostic_photo.html.twig', [
            'errors' => $errors,
            'diagnosis' => $diagnosis,
            'rawResponse' => $rawResponse,
            'plantName' => $plantName,
            'imageUrl' => $imageUrl,
            'analysisImage' => $analysisImage,
        ]);
    }

    private function formatTreatment(mixed $treatment): ?string
    {
        if (is_string($treatment) && $treatment !== '') {
            return $treatment;
        }

        if (!is_array($treatment)) {
            return null;
        }

        $sections = [];
        $map = [
            'biological' => 'Biologique',
            'chemical' => 'Chimique',
            'prevention' => 'Prevention',
        ];

        foreach ($map as $key => $label) {
            if (!array_key_exists($key, $treatment)) {
                continue;
            }

            $items = $treatment[$key];
            if (is_string($items)) {
                $items = [$items];
            }

            if (is_array($items)) {
                $items = array_values(array_filter(array_map('strval', $items)));
                if ($items !== []) {
                    $sections[] = $label . ': ' . implode(', ', $items);
                }
            }
        }

        if ($sections !== []) {
            return implode(' | ', $sections);
        }

        $flat = array_values(array_filter(array_map('strval', $treatment)));
        if ($flat === []) {
            return null;
        }

        return implode(', ', $flat);
    }
}
