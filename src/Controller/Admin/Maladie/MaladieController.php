<?php
namespace App\Controller\Admin\Maladie;

use App\Entity\Maladie\Maladie;
use App\Entity\Maladie\SolutionTraitement;
use App\Entity\User\Utilisateur;
use App\Repository\Maladie\MaladieRepository;
use App\Repository\Maladie\SolutionTraitementRepository;
use App\Service\Maladie\Weather\MaladieWeatherAlertMailer;
use App\Service\Maladie\Weather\MaladieWeatherRiskService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/maladie')]
#[IsGranted('ROLE_ADMIN')]
class MaladieController extends AbstractController
{
    private $em;
    private $maladieRepo;
    private $traitementRepo;

    public function __construct(
        EntityManagerInterface $em,
        MaladieRepository $maladieRepo,
        SolutionTraitementRepository $traitementRepo
    ) {
        $this->em = $em;
        $this->maladieRepo = $maladieRepo;
        $this->traitementRepo = $traitementRepo;
    }

    #[Route('/', name: 'admin_maladie_index')]
    public function index(Request $request): Response
    {
        $allMaladies = $this->maladieRepo->findAll();

        // Pagination
        $page  = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $total = count($allMaladies);
        $totalPages = max(1, (int) ceil($total / $limit));
        $page = min($page, $totalPages);
        $maladies = array_slice($allMaladies, ($page - 1) * $limit, $limit);

        return $this->render('admin/maladie/index.html.twig', [
            'maladies'      => $maladies,
            'totalMaladies' => $total,
            'currentPage'   => $page,
            'totalPages'    => $totalPages,
        ]);
    }

    #[Route('/weather', name: 'admin_maladie_weather', methods: ['GET'])]
    public function weatherDashboard(): Response
    {
        return $this->render('admin/maladie/weather.html.twig');
    }

    #[Route('/new', name: 'admin_maladie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $errors = [];
        $old = [];

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $nomScientifique = trim($request->request->get('nomScientifique', ''));
            $description = trim($request->request->get('description', ''));
            $symptomes = trim($request->request->get('symptomes', ''));
            $niveauGravite = $request->request->get('niveauGravite', 'moyen');
            $saisonFrequente = $request->request->get('saisonFrequente', '');
            $tempMin = $this->parseNullableFloat($request->request->get('tempMin'));
            $tempMax = $this->parseNullableFloat($request->request->get('tempMax'));
            $humiditeMin = $this->parseNullableInt($request->request->get('humiditeMin'));

            $old = compact('nom', 'nomScientifique', 'description', 'symptomes', 'niveauGravite', 'saisonFrequente', 'tempMin', 'tempMax', 'humiditeMin');

            if ($nom === '') {
                $errors['nom'] = 'Le nom est obligatoire.';
            } elseif (strlen($nom) < 3) {
                $errors['nom'] = 'Le nom doit contenir au moins 3 caracteres.';
            } elseif (strlen($nom) > 150) {
                $errors['nom'] = 'Le nom ne peut pas depasser 150 caracteres.';
            } elseif (!preg_match('/^[\p{L}\s\-\'\.]+$/u', $nom)) {
                $errors['nom'] = 'Le nom ne doit contenir que des lettres.';
            }

            if ($nomScientifique !== '') {
                if (strlen($nomScientifique) > 200) {
                    $errors['nomScientifique'] = 'Le nom scientifique ne peut pas depasser 200 caracteres.';
                } elseif (!preg_match('/^[\p{L}\s\-\.]+$/u', $nomScientifique)) {
                    $errors['nomScientifique'] = 'Le nom scientifique ne doit contenir que des lettres.';
                }
            }

            if ($description === '') {
                $errors['description'] = 'La description est obligatoire.';
            } elseif (strlen($description) < 10) {
                $errors['description'] = 'La description doit contenir au moins 10 caracteres.';
            } elseif (strlen($description) > 2000) {
                $errors['description'] = 'La description ne peut pas depasser 2000 caracteres.';
            }

            if ($symptomes === '') {
                $errors['symptomes'] = 'Les symptomes sont obligatoires.';
            } elseif (strlen($symptomes) < 10) {
                $errors['symptomes'] = 'Les symptomes doivent contenir au moins 10 caracteres.';
            } elseif (strlen($symptomes) > 2000) {
                $errors['symptomes'] = 'Les symptomes ne peuvent pas depasser 2000 caracteres.';
            }

            if (!in_array($niveauGravite, ['faible', 'moyen', 'eleve', 'critique'], true)) {
                $errors['niveauGravite'] = 'Niveau de gravite invalide.';
            }

            if (!in_array($saisonFrequente, ['', 'Printemps', 'Ete', 'Été', 'Automne', 'Hiver', 'Printemps-Ete', 'Printemps-Été', 'Printemps-Automne'], true)) {
                $errors['saisonFrequente'] = 'Saison invalide.';
            }

            $this->validateWeatherThresholds($tempMin, $tempMax, $humiditeMin, $errors);

            $imageFile = $request->files->get('imageFile');
            if ($imageFile) {
                $extension = strtolower($imageFile->getClientOriginalExtension());
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $errors['imageFile'] = 'Format non supporte. Utilisez JPG, PNG, WEBP ou GIF.';
                } elseif ($imageFile->getSize() > 2 * 1024 * 1024) {
                    $errors['imageFile'] = 'L image ne doit pas depasser 2 MB.';
                }
            }

            if ($errors === []) {
                $maladie = new Maladie();
                $maladie->setNom($nom);
                $maladie->setNomScientifique($nomScientifique ?: null);
                $maladie->setDescription($description);
                $maladie->setSymptomes($symptomes);
                $maladie->setNiveauGravite($niveauGravite);
                $maladie->setSaisonFrequente($saisonFrequente ?: null);
                $maladie->setTempMin($tempMin);
                $maladie->setTempMax($tempMax);
                $maladie->setHumiditeMin($humiditeMin);
                $maladie->setCreatedBy($this->getUser()->getId());

                if ($imageFile) {
                    $extension = strtolower($imageFile->getClientOriginalExtension());
                    $newFilename = $slugger->slug($nom) . '-' . uniqid() . '.' . $extension;
                    try {
                        $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/maladies', $newFilename);
                        $maladie->setImageUrl($newFilename);
                    } catch (FileException $e) {
                        $errors['imageFile'] = 'Erreur lors du telechargement de l image.';
                    }
                }

                if ($errors === []) {
                    $this->em->persist($maladie);
                    $this->em->flush();
                    $this->addFlash('success', 'Maladie "' . $nom . '" ajoutee avec succes !');
                    return $this->redirectToRoute('admin_maladie_edit', ['id' => $maladie->getId()]);
                }
            }
        }

        return $this->render('admin/maladie/new.html.twig', [
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    #[Route('/edit/{id}', name: 'admin_maladie_edit', methods: ['GET', 'POST'])]
    public function edit(Maladie $maladie, Request $request, SluggerInterface $slugger): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $nomScientifique = trim($request->request->get('nomScientifique', ''));
            $description = trim($request->request->get('description', ''));
            $symptomes = trim($request->request->get('symptomes', ''));
            $niveauGravite = $request->request->get('niveauGravite', 'moyen');
            $saisonFrequente = $request->request->get('saisonFrequente', '');
            $tempMin = $this->parseNullableFloat($request->request->get('tempMin'));
            $tempMax = $this->parseNullableFloat($request->request->get('tempMax'));
            $humiditeMin = $this->parseNullableInt($request->request->get('humiditeMin'));

            if ($nom === '') {
                $errors['nom'] = 'Le nom est obligatoire.';
            } elseif (strlen($nom) < 3) {
                $errors['nom'] = 'Le nom doit contenir au moins 3 caracteres.';
            } elseif (strlen($nom) > 150) {
                $errors['nom'] = 'Le nom ne peut pas depasser 150 caracteres.';
            } elseif (!preg_match('/^[\p{L}\s\-\'\.]+$/u', $nom)) {
                $errors['nom'] = 'Le nom ne doit contenir que des lettres.';
            }

            if ($nomScientifique !== '') {
                if (strlen($nomScientifique) > 200) {
                    $errors['nomScientifique'] = 'Le nom scientifique ne peut pas depasser 200 caracteres.';
                } elseif (!preg_match('/^[\p{L}\s\-\.]+$/u', $nomScientifique)) {
                    $errors['nomScientifique'] = 'Le nom scientifique ne doit contenir que des lettres.';
                }
            }

            if ($description === '') {
                $errors['description'] = 'La description est obligatoire.';
            } elseif (strlen($description) < 10) {
                $errors['description'] = 'La description doit contenir au moins 10 caracteres.';
            } elseif (strlen($description) > 2000) {
                $errors['description'] = 'La description ne peut pas depasser 2000 caracteres.';
            }

            if ($symptomes === '') {
                $errors['symptomes'] = 'Les symptomes sont obligatoires.';
            } elseif (strlen($symptomes) < 10) {
                $errors['symptomes'] = 'Les symptomes doivent contenir au moins 10 caracteres.';
            } elseif (strlen($symptomes) > 2000) {
                $errors['symptomes'] = 'Les symptomes ne peuvent pas depasser 2000 caracteres.';
            }

            if (!in_array($niveauGravite, ['faible', 'moyen', 'eleve', 'critique'], true)) {
                $errors['niveauGravite'] = 'Niveau de gravite invalide.';
            }

            if (!in_array($saisonFrequente, ['', 'Printemps', 'Ete', 'Été', 'Automne', 'Hiver', 'Printemps-Ete', 'Printemps-Été', 'Printemps-Automne'], true)) {
                $errors['saisonFrequente'] = 'Saison invalide.';
            }

            $this->validateWeatherThresholds($tempMin, $tempMax, $humiditeMin, $errors);

            $imageFile = $request->files->get('imageFile');
            if ($imageFile) {
                $extension = strtolower($imageFile->getClientOriginalExtension());
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $errors['imageFile'] = 'Format non supporte. Utilisez JPG, PNG, WEBP ou GIF.';
                } elseif ($imageFile->getSize() > 2 * 1024 * 1024) {
                    $errors['imageFile'] = 'L image ne doit pas depasser 2 MB.';
                }
            }

            if ($errors === []) {
                $maladie->setNom($nom);
                $maladie->setNomScientifique($nomScientifique ?: null);
                $maladie->setDescription($description);
                $maladie->setSymptomes($symptomes);
                $maladie->setNiveauGravite($niveauGravite);
                $maladie->setSaisonFrequente($saisonFrequente ?: null);
                $maladie->setTempMin($tempMin);
                $maladie->setTempMax($tempMax);
                $maladie->setHumiditeMin($humiditeMin);

                if ($imageFile) {
                    $extension = strtolower($imageFile->getClientOriginalExtension());
                    $newFilename = $slugger->slug($nom) . '-' . uniqid() . '.' . $extension;
                    try {
                        $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/maladies', $newFilename);
                        $maladie->setImageUrl($newFilename);
                    } catch (FileException $e) {
                        $errors['imageFile'] = 'Erreur lors du telechargement de l image.';
                    }
                }

                if ($errors === []) {
                    $this->em->flush();
                    $this->addFlash('success', 'Maladie "' . $nom . '" modifiee avec succes !');
                    return $this->redirectToRoute('admin_maladie_edit', ['id' => $maladie->getId()]);
                }
            }
        }

        return $this->render('admin/maladie/edit.html.twig', [
            'maladie' => $maladie,
            'traitements' => $this->traitementRepo->findByMaladieId($maladie->getId()),
            'errors' => $errors,
        ]);
    }

    #[Route('/show/{id}', name: 'admin_maladie_show')]
    public function show(Maladie $maladie): Response
    {
        return $this->render('admin/maladie/show.html.twig', [
            'maladie' => $maladie,
            'traitements' => $this->traitementRepo->findByMaladieId($maladie->getId()),
        ]);
    }

    #[Route('/{id}/weather-risk', name: 'admin_maladie_weather_risk', methods: ['GET'])]
    public function weatherRisk(
        Maladie $maladie,
        Request $request,
        MaladieWeatherRiskService $weatherRiskService,
        MaladieWeatherAlertMailer $alertMailer
    ): JsonResponse
    {
        $user = $this->getUser();
        $latitude = $request->query->get('lat');
        $longitude = $request->query->get('lon');
        $city = trim((string) $request->query->get('city', ''));

        if ($city === '' && $user instanceof Utilisateur) {
            $city = trim((string) $user->getVille());
        }

        $isTestMode = $request->query->getBoolean('test');

        if ($isTestMode) {
            if ($latitude === null || $longitude === null || !is_numeric($latitude) || !is_numeric($longitude)) {
                return $this->json(['success' => false, 'error' => 'Latitude et longitude invalides.'], Response::HTTP_BAD_REQUEST);
            }

            $temp = $request->query->get('temp');
            $humidity = $request->query->get('humidity');
            $rain = $request->query->get('rain', 0);
            $wind = $request->query->get('wind', 0);
            $condition = $request->query->get('condition', 'Simulation manuelle');

            if ($temp === null || $humidity === null || !is_numeric($temp) || !is_numeric($humidity) || !is_numeric($rain) || !is_numeric($wind)) {
                return $this->json(['success' => false, 'error' => 'Les parametres de test sont invalides. Utilisez temp, humidity, rain et wind numeriques.'], Response::HTTP_BAD_REQUEST);
            }

            return $this->json([
                'success' => true,
                'data' => $weatherRiskService->evaluateFromWeatherData($maladie, [
                    'temperature' => (float) $temp,
                    'humidity' => (int) $humidity,
                    'rain' => (float) $rain,
                    'wind' => (float) $wind,
                    'condition' => (string) $condition,
                ], (float) $latitude, (float) $longitude),
            ]);
        }

        if (!$weatherRiskService->isConfigured()) {
            return $this->json(['success' => false, 'error' => 'La cle API meteo n est pas encore configuree dans l environnement.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            if ($city !== '') {
                $data = $weatherRiskService->evaluateByCity($maladie, $city);
                return $this->json($this->buildWeatherRiskResponse($data, $user, $city, $weatherRiskService, $alertMailer));
            }

            if ($latitude === null || $longitude === null || !is_numeric($latitude) || !is_numeric($longitude)) {
                return $this->json(['success' => false, 'error' => 'Aucune ville utilisateur ou coordonnee valide n a ete trouvee.'], Response::HTTP_BAD_REQUEST);
            }

            $data = $weatherRiskService->evaluate($maladie, (float) $latitude, (float) $longitude);
            return $this->json($this->buildWeatherRiskResponse($data, $user, (string) ($data['weather']['city'] ?? $city), $weatherRiskService, $alertMailer));
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_GATEWAY);
        }
    }

    #[Route('/delete/{id}', name: 'admin_maladie_delete', methods: ['POST'])]
    public function delete(Maladie $maladie, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete' . $maladie->getId(), $request->request->get('_token'))) {
            $this->em->remove($maladie);
            $this->em->flush();
            $this->addFlash('success', 'Maladie "' . $maladie->getNom() . '" supprimee avec succes !');
        }

        return $this->redirectToRoute('admin_maladie_index');
    }

    #[Route('/{id}/solution/new', name: 'admin_solution_new', methods: ['POST'])]
    public function newSolution(Maladie $maladie, Request $request): Response
    {
        $errors = [];
        $titre = trim($request->request->get('titre', ''));
        $solution = trim($request->request->get('solution', ''));
        $etapes = trim($request->request->get('etapes', ''));
        $produitsRecommandes = trim($request->request->get('produitsRecommandes', ''));
        $conseilsPrevention = trim($request->request->get('conseilsPrevention', ''));
        $dureeTraitement = trim($request->request->get('dureeTraitement', ''));

        if ($titre === '') {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (strlen($titre) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 caracteres.';
        } elseif (strlen($titre) > 200) {
            $errors['titre'] = 'Le titre ne peut pas depasser 200 caracteres.';
        }

        if ($solution === '') {
            $errors['solution'] = 'La solution est obligatoire.';
        } elseif (strlen($solution) < 10) {
            $errors['solution'] = 'La solution doit contenir au moins 10 caracteres.';
        } elseif (strlen($solution) > 2000) {
            $errors['solution'] = 'La solution ne peut pas depasser 2000 caracteres.';
        }

        if ($dureeTraitement !== '' && strlen($dureeTraitement) > 100) {
            $errors['dureeTraitement'] = 'La duree ne peut pas depasser 100 caracteres.';
        }

        if ($errors === []) {
            $traitement = new SolutionTraitement();
            $traitement->setMaladie($maladie);
            $traitement->setTitre($titre);
            $traitement->setSolution($solution);
            $traitement->setEtapes($etapes ?: null);
            $traitement->setProduitsRecommandes($produitsRecommandes ?: null);
            $traitement->setConseilsPrevention($conseilsPrevention ?: null);
            $traitement->setDureeTraitement($dureeTraitement ?: null);
            $traitement->setCreatedBy($this->getUser()->getId());

            $this->em->persist($traitement);
            $this->em->flush();
            $this->addFlash('success', 'Solution "' . $titre . '" ajoutee avec succes !');
        } else {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->redirectToRoute('admin_maladie_edit', ['id' => $maladie->getId()]);
    }

    #[Route('/solution/edit/{id}', name: 'admin_solution_edit', methods: ['POST'])]
    public function editSolution(SolutionTraitement $traitement, Request $request): Response
    {
        $errors = [];
        $titre = trim($request->request->get('titre', ''));
        $solution = trim($request->request->get('solution', ''));
        $etapes = trim($request->request->get('etapes', ''));
        $produitsRecommandes = trim($request->request->get('produitsRecommandes', ''));
        $conseilsPrevention = trim($request->request->get('conseilsPrevention', ''));
        $dureeTraitement = trim($request->request->get('dureeTraitement', ''));

        if ($titre === '') {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (strlen($titre) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 caracteres.';
        } elseif (strlen($titre) > 200) {
            $errors['titre'] = 'Le titre ne peut pas depasser 200 caracteres.';
        }

        if ($solution === '') {
            $errors['solution'] = 'La solution est obligatoire.';
        } elseif (strlen($solution) < 10) {
            $errors['solution'] = 'La solution doit contenir au moins 10 caracteres.';
        } elseif (strlen($solution) > 2000) {
            $errors['solution'] = 'La solution ne peut pas depasser 2000 caracteres.';
        }

        if ($dureeTraitement !== '' && strlen($dureeTraitement) > 100) {
            $errors['dureeTraitement'] = 'La duree ne peut pas depasser 100 caracteres.';
        }

        if ($errors === []) {
            $traitement->setTitre($titre);
            $traitement->setSolution($solution);
            $traitement->setEtapes($etapes ?: null);
            $traitement->setProduitsRecommandes($produitsRecommandes ?: null);
            $traitement->setConseilsPrevention($conseilsPrevention ?: null);
            $traitement->setDureeTraitement($dureeTraitement ?: null);
            $this->em->flush();
            $this->addFlash('success', 'Solution "' . $titre . '" modifiee avec succes !');
        } else {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->redirectToRoute('admin_maladie_edit', ['id' => $traitement->getMaladie()->getId()]);
    }

    #[Route('/solution/delete/{id}', name: 'admin_solution_delete', methods: ['POST'])]
    public function deleteSolution(SolutionTraitement $traitement, Request $request): Response
    {
        $maladieId = $traitement->getMaladie()->getId();

        if ($this->isCsrfTokenValid('delete-solution' . $traitement->getId(), $request->request->get('_token'))) {
            $this->em->remove($traitement);
            $this->em->flush();
            $this->addFlash('success', 'Solution supprimee avec succes !');
        }

        return $this->redirectToRoute('admin_maladie_edit', ['id' => $maladieId]);
    }

    private function parseNullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function parseNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param array<string, string> $errors
     */
    private function validateWeatherThresholds(?float $tempMin, ?float $tempMax, ?int $humiditeMin, array &$errors): void
    {
        if ($tempMin !== null && ($tempMin < -100 || $tempMin > 100)) {
            $errors['tempMin'] = 'La temperature minimale doit etre entre -100 et 100.';
        }

        if ($tempMax !== null && ($tempMax < -100 || $tempMax > 100)) {
            $errors['tempMax'] = 'La temperature maximale doit etre entre -100 et 100.';
        }

        if ($tempMin !== null && $tempMax !== null && $tempMin > $tempMax) {
            $errors['tempRange'] = 'La temperature minimale doit etre inferieure ou egale a la temperature maximale.';
        }

        if ($humiditeMin !== null && ($humiditeMin < 0 || $humiditeMin > 100)) {
            $errors['humiditeMin'] = 'L humidite minimale doit etre comprise entre 0 et 100.';
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function buildWeatherRiskResponse(
        array $data,
        mixed $user,
        string $city,
        MaladieWeatherRiskService $weatherRiskService,
        MaladieWeatherAlertMailer $alertMailer
    ): array {
        $mailStatus = [
            'attempted' => false,
            'sent' => false,
            'error' => null,
        ];

        if (
            $user instanceof Utilisateur
            && $weatherRiskService->isAlertRiskLevel((string) ($data['risk']['level'] ?? 'faible'))
        ) {
            $mailStatus['attempted'] = true;

            try {
                $alertMailer->sendRiskAlert($user, $city, [
                    'main' => [
                        'temp' => $data['weather']['temperature'] ?? null,
                        'humidity' => $data['weather']['humidity'] ?? null,
                    ],
                    'rain' => [
                        '1h' => $data['weather']['rain'] ?? 0,
                    ],
                    'wind' => [
                        'speed' => $data['weather']['wind'] ?? null,
                    ],
                    'weather' => [[
                        'description' => $data['weather']['condition'] ?? null,
                    ]],
                    'name' => $data['weather']['city'] ?? $city,
                ], [$data]);
                $mailStatus['sent'] = true;
            } catch (\Throwable $e) {
                $mailStatus['error'] = $e->getMessage();
            }
        }

        return [
            'success' => true,
            'data' => $data,
            'mail' => $mailStatus,
        ];
    }
}
