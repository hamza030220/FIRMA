<?php

namespace App\Controller\User;

use App\Entity\User\Utilisateur;
use App\Repository\Maladie\MaladieRepository;
use App\Service\Maladie\Weather\MaladieWeatherAlertMailer;
use App\Service\Maladie\Weather\MaladieWeatherAutoAlertService;
use App\Service\Maladie\Weather\MaladieWeatherRiskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'user_dashboard')]
    public function dashboard(MaladieWeatherAutoAlertService $autoAlertService): Response
    {
        $this->triggerAutoAlert($autoAlertService);

        return $this->render('user/dashboard.html.twig');
    }

    #[Route('/marketplace', name: 'user_marketplace')]
    public function marketplace(MaladieWeatherAutoAlertService $autoAlertService): Response
    {
        $this->triggerAutoAlert($autoAlertService);

        return $this->render('user/marketplace/index.html.twig');
    }

    #[Route('/forum', name: 'user_forum')]
    public function forum(MaladieWeatherAutoAlertService $autoAlertService): Response
    {
        $this->triggerAutoAlert($autoAlertService);

        return $this->render('user/forum/index.html.twig');
    }

    #[Route('/maladies', name: 'user_maladie')]
    public function maladie(Request $request, MaladieRepository $maladieRepository, MaladieWeatherAutoAlertService $autoAlertService): Response
    {
        $this->triggerAutoAlert($autoAlertService);

        $keyword = $request->query->get('q', '');
        $tri     = $request->query->get('tri', 'nom');
        $gravite = $request->query->get('gravite', '');

        $allMaladies = $maladieRepository->searchAndFilter($keyword, $tri, $gravite);

        // Pagination
        $page  = max(1, $request->query->getInt('page', 1));
        $limit = 9;
        $total = count($allMaladies);
        $totalPages = max(1, (int) ceil($total / $limit));
        $page = min($page, $totalPages);
        $maladies = array_slice($allMaladies, ($page - 1) * $limit, $limit);

        return $this->render('user/maladie/index.html.twig', [
            'maladies'    => $maladies,
            'keyword'     => $keyword,
            'tri'         => $tri,
            'gravite'     => $gravite,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'listRoute'   => 'user_maladie',
            'paginationRouteParams' => array_filter([
                'q' => $keyword !== '' ? $keyword : null,
                'tri' => $tri !== '' ? $tri : null,
                'gravite' => $gravite !== '' ? $gravite : null,
            ], static fn ($value) => $value !== null),
        ]);
    }

    #[Route('/maladies/meteo', name: 'user_maladie_weather')]
    public function maladieWeather(MaladieWeatherAutoAlertService $autoAlertService): Response
    {
        $this->triggerAutoAlert($autoAlertService);

        return $this->render('user/maladie/weather.html.twig');
    }

    #[Route('/maladies/meteo/data', name: 'user_maladie_weather_data', methods: ['GET'])]
    public function maladieWeatherData(
        MaladieRepository $maladieRepository,
        MaladieWeatherRiskService $weatherRiskService,
        MaladieWeatherAlertMailer $alertMailer,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non authentifie.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $city = trim((string) $user->getVille());
        if ($city === '') {
            return $this->json([
                'success' => false,
                'error' => 'La ville de votre compte est vide. Mettez a jour votre profil pour utiliser la meteo.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$weatherRiskService->isConfigured()) {
            return $this->json([
                'success' => false,
                'error' => 'La cle API OpenWeatherMap n est pas configuree.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $weather = $weatherRiskService->getWeatherByCity($city);
            $riskAnalyses = [];
            $mailStatus = [
                'attempted' => false,
                'sent' => false,
                'error' => null,
            ];

            foreach ($maladieRepository->findAll() as $maladie) {
                $analysis = $weatherRiskService->evaluateFromApiPayload($maladie, $weather);
                if ($weatherRiskService->isAlertRiskLevel((string) ($analysis['risk']['level'] ?? 'faible'))) {
                    $riskAnalyses[] = $analysis;
                }
            }

            usort($riskAnalyses, static function (array $left, array $right): int {
                $rank = ['critique' => 3, 'eleve' => 2, 'moyen' => 1, 'faible' => 0];
                return ($rank[$right['risk']['level'] ?? 'faible'] ?? 0) <=> ($rank[$left['risk']['level'] ?? 'faible'] ?? 0);
            });

            if ($riskAnalyses !== []) {
                $mailStatus['attempted'] = true;
                try {
                    $alertMailer->sendRiskAlert($user, $city, $weather, $riskAnalyses);
                    $mailStatus['sent'] = true;
                } catch (\Throwable $e) {
                    $mailStatus['error'] = $e->getMessage();
                }
            }

            return $this->json([
                'success' => true,
                'city' => $weather['name'] ?? $city,
                'weather' => [
                    'temperature' => $weather['main']['temp'] ?? null,
                    'humidity' => $weather['main']['humidity'] ?? null,
                    'rain' => $weather['rain']['1h'] ?? ($weather['rain']['3h'] ?? 0),
                    'wind' => $weather['wind']['speed'] ?? null,
                    'condition' => $weather['weather'][0]['description'] ?? null,
                ],
                'alerts' => $riskAnalyses,
                'mail' => $mailStatus,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/evenements', name: 'user_evenements')]
    public function evenements(MaladieWeatherAutoAlertService $autoAlertService): Response
    {
        $this->triggerAutoAlert($autoAlertService);

        return $this->render('user/event/index.html.twig');
    }

    #[Route('/profil', name: 'user_profile')]
    public function profile(MaladieWeatherAutoAlertService $autoAlertService): Response
    {
        $this->triggerAutoAlert($autoAlertService);

        return $this->render('user/profile/index.html.twig');
    }

    private function triggerAutoAlert(MaladieWeatherAutoAlertService $autoAlertService): void
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return;
        }

        $result = $autoAlertService->checkAndSendForUser($user);
        if ($result['sent']) {
            $this->addFlash('success', 'Alerte meteo envoyee automatiquement pour ' . ($result['city'] ?? 'votre ville') . '.');
        } elseif ($result['error']) {
            $this->addFlash('error', 'Echec de l alerte meteo automatique: ' . $result['error']);
        }
    }
}
