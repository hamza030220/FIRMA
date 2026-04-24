<?php
namespace App\Controller\Admin\Maladie;

use App\Entity\Maladie\Maladie;
use App\Entity\Maladie\SolutionTraitement;
use App\Repository\Maladie\MaladieRepository;
use App\Repository\Maladie\SolutionTraitementRepository;
use App\Entity\User\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\Maladie\Weather\MaladieWeatherRiskService;
use App\Service\Maladie\Weather\MaladieWeatherAlertMailer;

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

    // ==================== LISTE ====================
    #[Route('/', name: 'admin_maladie_index')]
    public function index(): Response
    {
        return $this->render('admin/maladie/index.html.twig', [
            'maladies'      => $this->maladieRepo->findAll(),
            'totalMaladies' => $this->maladieRepo->countAll(),
        ]);
    }

    #[Route('/weather', name: 'admin_maladie_weather', methods: ['GET'])]
    public function weather(): Response
    {
        return $this->render('admin/maladie/weather.html.twig');
    }

    #[Route('/weather/data', name: 'admin_maladie_weather_data', methods: ['GET'])]
    public function weatherData(
        MaladieRepository $maladieRepository,
        MaladieWeatherRiskService $weatherRiskService,
        MaladieWeatherAlertMailer $alertMailer
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
                'error' => 'La ville du compte connecte est vide. Mettez a jour le profil admin.',
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

    // ==================== AJOUTER MALADIE ====================
    #[Route('/new', name: 'admin_maladie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger): Response
    {
        $errors = [];
        $old = [];

        if ($request->isMethod('POST')) {
            $nom             = trim($request->request->get('nom', ''));
            $nomScientifique = trim($request->request->get('nomScientifique', ''));
            $description     = trim($request->request->get('description', ''));
            $symptomes       = trim($request->request->get('symptomes', ''));
            $niveauGravite   = $request->request->get('niveauGravite', 'moyen');
            $saisonFrequente = $request->request->get('saisonFrequente', '');
            $tempMinRaw      = trim((string) $request->request->get('tempMin', ''));
            $tempMaxRaw      = trim((string) $request->request->get('tempMax', ''));
            $humiditeMinRaw  = trim((string) $request->request->get('humiditeMin', ''));
            $tempMin         = $tempMinRaw === '' ? null : str_replace(',', '.', $tempMinRaw);
            $tempMax         = $tempMaxRaw === '' ? null : str_replace(',', '.', $tempMaxRaw);
            $humiditeMin     = $humiditeMinRaw === '' ? null : $humiditeMinRaw;

            $old = compact('nom', 'nomScientifique', 'description', 'symptomes', 'niveauGravite', 'saisonFrequente', 'tempMinRaw', 'tempMaxRaw', 'humiditeMinRaw');

            // Validations
            if (empty($nom)) {
                $errors['nom'] = 'Le nom est obligatoire.';
            } elseif (strlen($nom) < 3) {
                $errors['nom'] = 'Le nom doit contenir au moins 3 caractères.';
            } elseif (strlen($nom) > 150) {
                $errors['nom'] = 'Le nom ne peut pas dépasser 150 caractères.';
            } elseif (!preg_match('/^[\p{L}\s\-\'\.]+$/u', $nom)) {
                $errors['nom'] = 'Le nom ne doit contenir que des lettres.';
            } elseif ($this->maladieRepo->findOneByNormalizedName($nom) !== null) {
                $errors['nom'] = 'Cette maladie existe déjà. Veuillez choisir un autre nom.';
            }

            if (!empty($nomScientifique)) {
                if (strlen($nomScientifique) > 200) {
                    $errors['nomScientifique'] = 'Le nom scientifique ne peut pas dépasser 200 caractères.';
                } elseif (!preg_match('/^[\p{L}\s\-\.]+$/u', $nomScientifique)) {
                    $errors['nomScientifique'] = 'Le nom scientifique ne doit contenir que des lettres.';
                }
            }

            if (empty($description)) {
                $errors['description'] = 'La description est obligatoire.';
            } elseif (strlen($description) < 10) {
                $errors['description'] = 'La description doit contenir au moins 10 caractères.';
            } elseif (strlen($description) > 2000) {
                $errors['description'] = 'La description ne peut pas dépasser 2000 caractères.';
            }

            if (empty($symptomes)) {
                $errors['symptomes'] = 'Les symptômes sont obligatoires.';
            } elseif (strlen($symptomes) < 10) {
                $errors['symptomes'] = 'Les symptômes doivent contenir au moins 10 caractères.';
            } elseif (strlen($symptomes) > 2000) {
                $errors['symptomes'] = 'Les symptômes ne peuvent pas dépasser 2000 caractères.';
            }

            if (!in_array($niveauGravite, ['faible', 'moyen', 'eleve', 'critique'])) {
                $errors['niveauGravite'] = 'Niveau de gravité invalide.';
            }

            if (!in_array($saisonFrequente, ['', 'Printemps', 'Été', 'Automne', 'Hiver', 'Printemps-Été', 'Printemps-Automne'])) {
                $errors['saisonFrequente'] = 'Saison invalide.';
            }

            if ($tempMin !== null && !is_numeric($tempMin)) {
                $errors['tempMin'] = 'La temperature minimale doit etre un nombre valide.';
            }
            if ($tempMax !== null && !is_numeric($tempMax)) {
                $errors['tempMax'] = 'La temperature maximale doit etre un nombre valide.';
            }
            if ($humiditeMin !== null && (!ctype_digit($humiditeMin) || (int) $humiditeMin < 0 || (int) $humiditeMin > 100)) {
                $errors['humiditeMin'] = 'L humidite minimale doit etre comprise entre 0 et 100.';
            }

            if ($tempMin !== null && $tempMax !== null && is_numeric($tempMin) && is_numeric($tempMax) && (float) $tempMin > (float) $tempMax) {
                $errors['tempMin'] = 'La temperature minimale doit etre inferieure ou egale a la temperature maximale.';
            }

            $imageFile = $request->files->get('imageFile');
            if ($imageFile) {
                $extension = strtolower($imageFile->getClientOriginalExtension());
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $errors['imageFile'] = 'Format non supporté. Utilisez JPG, PNG, WEBP ou GIF.';
                } elseif ($imageFile->getSize() > 2 * 1024 * 1024) {
                    $errors['imageFile'] = 'L\'image ne doit pas dépasser 2 MB.';
                }
            }

            if (empty($errors)) {
                $maladie = new Maladie();
                $maladie->setNom($nom);
                $maladie->setNomScientifique($nomScientifique ?: null);
                $maladie->setDescription($description);
                $maladie->setSymptomes($symptomes);
                $maladie->setNiveauGravite($niveauGravite);
                $maladie->setSaisonFrequente($saisonFrequente ?: null);
                $maladie->setTempMin($tempMin === null ? null : (float) $tempMin);
                $maladie->setTempMax($tempMax === null ? null : (float) $tempMax);
                $maladie->setHumiditeMin($humiditeMin === null ? null : (int) $humiditeMin);
                $maladie->setCreatedBy($this->getUser()->getId());

                if ($imageFile) {
                    $extension   = strtolower($imageFile->getClientOriginalExtension());
                    $newFilename = $slugger->slug($nom) . '-' . uniqid() . '.' . $extension;
                    try {
                        $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/maladies', $newFilename);
                        $maladie->setImageUrl($newFilename);
                    } catch (FileException $e) {
                        $errors['imageFile'] = 'Erreur lors du téléchargement de l\'image.';
                    }
                }

                if (empty($errors)) {
                    $this->em->persist($maladie);
                    $this->em->flush();
                    $this->addFlash('success', 'Maladie "' . $nom . '" ajoutée avec succès !');
                    return $this->redirectToRoute('admin_maladie_edit', ['id' => $maladie->getId()]);
                }
            }
        }

        foreach ($errors as $err) {
            $this->addFlash('danger', $err);
        }

        return $this->render('admin/maladie/new.html.twig', [
            'errors' => [],
            'old'    => $old,
        ]);
    }

    // ==================== MODIFIER MALADIE ====================
    #[Route('/edit/{id}', name: 'admin_maladie_edit', methods: ['GET', 'POST'])]
    public function edit(Maladie $maladie, Request $request, SluggerInterface $slugger): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            $nom             = trim($request->request->get('nom', ''));
            $nomScientifique = trim($request->request->get('nomScientifique', ''));
            $description     = trim($request->request->get('description', ''));
            $symptomes       = trim($request->request->get('symptomes', ''));
            $niveauGravite   = $request->request->get('niveauGravite', 'moyen');
            $saisonFrequente = $request->request->get('saisonFrequente', '');
            $tempMinRaw      = trim((string) $request->request->get('tempMin', ''));
            $tempMaxRaw      = trim((string) $request->request->get('tempMax', ''));
            $humiditeMinRaw  = trim((string) $request->request->get('humiditeMin', ''));

            if (empty($nom)) {
                $errors['nom'] = 'Le nom est obligatoire.';
            } elseif (strlen($nom) < 3) {
                $errors['nom'] = 'Le nom doit contenir au moins 3 caractères.';
            } elseif (strlen($nom) > 150) {
                $errors['nom'] = 'Le nom ne peut pas dépasser 150 caractères.';
            } elseif (!preg_match('/^[\p{L}\s\-\'\.]+$/u', $nom)) {
                $errors['nom'] = 'Le nom ne doit contenir que des lettres.';
            } elseif ($this->maladieRepo->findOneByNormalizedName($nom, $maladie->getId()) !== null) {
                $errors['nom'] = 'Cette maladie existe déjà. Veuillez choisir un autre nom.';
            }

            if (!empty($nomScientifique)) {
                if (strlen($nomScientifique) > 200) {
                    $errors['nomScientifique'] = 'Le nom scientifique ne peut pas dépasser 200 caractères.';
                } elseif (!preg_match('/^[\p{L}\s\-\.]+$/u', $nomScientifique)) {
                    $errors['nomScientifique'] = 'Le nom scientifique ne doit contenir que des lettres.';
                }
            }

            if (empty($description)) {
                $errors['description'] = 'La description est obligatoire.';
            } elseif (strlen($description) < 10) {
                $errors['description'] = 'La description doit contenir au moins 10 caractères.';
            } elseif (strlen($description) > 2000) {
                $errors['description'] = 'La description ne peut pas dépasser 2000 caractères.';
            }

            if (empty($symptomes)) {
                $errors['symptomes'] = 'Les symptômes sont obligatoires.';
            } elseif (strlen($symptomes) < 10) {
                $errors['symptomes'] = 'Les symptômes doivent contenir au moins 10 caractères.';
            } elseif (strlen($symptomes) > 2000) {
                $errors['symptomes'] = 'Les symptômes ne peuvent pas dépasser 2000 caractères.';
            }

            if (!in_array($niveauGravite, ['faible', 'moyen', 'eleve', 'critique'])) {
                $errors['niveauGravite'] = 'Niveau de gravité invalide.';
            }

            if (!in_array($saisonFrequente, ['', 'Printemps', 'Été', 'Automne', 'Hiver', 'Printemps-Été', 'Printemps-Automne'])) {
                $errors['saisonFrequente'] = 'Saison invalide.';
            }

            $tempMin = $tempMinRaw === '' ? null : str_replace(',', '.', $tempMinRaw);
            $tempMax = $tempMaxRaw === '' ? null : str_replace(',', '.', $tempMaxRaw);
            $humiditeMin = $humiditeMinRaw === '' ? null : $humiditeMinRaw;

            if ($tempMin !== null && !is_numeric($tempMin)) {
                $errors['tempMin'] = 'La temperature minimale doit etre un nombre valide.';
            }
            if ($tempMax !== null && !is_numeric($tempMax)) {
                $errors['tempMax'] = 'La temperature maximale doit etre un nombre valide.';
            }
            if ($humiditeMin !== null && (!ctype_digit($humiditeMin) || (int) $humiditeMin < 0 || (int) $humiditeMin > 100)) {
                $errors['humiditeMin'] = 'L humidite minimale doit etre comprise entre 0 et 100.';
            }

            if ($tempMin !== null && $tempMax !== null && is_numeric($tempMin) && is_numeric($tempMax) && (float) $tempMin > (float) $tempMax) {
                $errors['tempMin'] = 'La temperature minimale doit etre inferieure ou egale a la temperature maximale.';
            }

            $imageFile = $request->files->get('imageFile');
            if ($imageFile) {
                $extension = strtolower($imageFile->getClientOriginalExtension());
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $errors['imageFile'] = 'Format non supporté. Utilisez JPG, PNG, WEBP ou GIF.';
                } elseif ($imageFile->getSize() > 2 * 1024 * 1024) {
                    $errors['imageFile'] = 'L\'image ne doit pas dépasser 2 MB.';
                }
            }

            if (empty($errors)) {
                $maladie->setNom($nom);
                $maladie->setNomScientifique($nomScientifique ?: null);
                $maladie->setDescription($description);
                $maladie->setSymptomes($symptomes);
                $maladie->setNiveauGravite($niveauGravite);
                $maladie->setSaisonFrequente($saisonFrequente ?: null);
                $maladie->setTempMin($tempMin === null ? null : (float) $tempMin);
                $maladie->setTempMax($tempMax === null ? null : (float) $tempMax);
                $maladie->setHumiditeMin($humiditeMin === null ? null : (int) $humiditeMin);

                if ($imageFile) {
                    $extension   = strtolower($imageFile->getClientOriginalExtension());
                    $newFilename = $slugger->slug($nom) . '-' . uniqid() . '.' . $extension;
                    try {
                        $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/maladies', $newFilename);
                        $maladie->setImageUrl($newFilename);
                    } catch (FileException $e) {
                        $errors['imageFile'] = 'Erreur lors du téléchargement de l\'image.';
                    }
                }

                if (empty($errors)) {
                    $this->em->flush();
                    $this->addFlash('success', 'Maladie "' . $nom . '" modifiée avec succès !');
                    return $this->redirectToRoute('admin_maladie_edit', ['id' => $maladie->getId()]);
                }
            }
        }

        foreach ($errors as $err) {
            $this->addFlash('danger', $err);
        }

        return $this->render('admin/maladie/edit.html.twig', [
            'maladie'     => $maladie,
            'traitements' => $this->traitementRepo->findByMaladieId($maladie->getId()),
            'errors'      => [],
        ]);
    }

    // ==================== VOIR DÉTAIL ====================
    #[Route('/show/{id}', name: 'admin_maladie_show')]
    public function show(Maladie $maladie): Response
    {
        return $this->render('admin/maladie/show.html.twig', [
            'maladie'     => $maladie,
            'traitements' => $this->traitementRepo->findByMaladieId($maladie->getId()),
        ]);
    }

    // ==================== SUPPRIMER MALADIE ====================
    #[Route('/delete/{id}', name: 'admin_maladie_delete', methods: ['POST'])]
    public function delete(Maladie $maladie, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete' . $maladie->getId(), $request->request->get('_token'))) {
            $this->em->remove($maladie);
            $this->em->flush();
            $this->addFlash('success', 'Maladie "' . $maladie->getNom() . '" supprimée avec succès !');
        }
        return $this->redirectToRoute('admin_maladie_index');
    }

    // ==================== AJOUTER SOLUTION ====================
    #[Route('/{id}/solution/new', name: 'admin_solution_new', methods: ['POST'])]
    public function newSolution(Maladie $maladie, Request $request): Response
    {
        $errors = [];

        $titre             = trim($request->request->get('titre', ''));
        $solution          = trim($request->request->get('solution', ''));
        $etapes            = trim($request->request->get('etapes', ''));
        $produitsRecommandes = trim($request->request->get('produitsRecommandes', ''));
        $conseilsPrevention  = trim($request->request->get('conseilsPrevention', ''));
        $dureeTraitement   = trim($request->request->get('dureeTraitement', ''));

        // Validations
        if (empty($titre)) {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (strlen($titre) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
        } elseif (strlen($titre) > 200) {
            $errors['titre'] = 'Le titre ne peut pas dépasser 200 caractères.';
        }

        if (empty($solution)) {
            $errors['solution'] = 'La solution est obligatoire.';
        } elseif (strlen($solution) < 10) {
            $errors['solution'] = 'La solution doit contenir au moins 10 caractères.';
        } elseif (strlen($solution) > 2000) {
            $errors['solution'] = 'La solution ne peut pas dépasser 2000 caractères.';
        }

        if (!empty($dureeTraitement) && strlen($dureeTraitement) > 100) {
            $errors['dureeTraitement'] = 'La durée ne peut pas dépasser 100 caractères.';
        }

        if (empty($errors)) {
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

            $this->addFlash('success', 'Solution "' . $titre . '" ajoutée avec succès !');
        } else {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->redirectToRoute('admin_maladie_edit', ['id' => $maladie->getId()]);
    }

    // ==================== MODIFIER SOLUTION ====================
    #[Route('/solution/edit/{id}', name: 'admin_solution_edit', methods: ['POST'])]
    public function editSolution(SolutionTraitement $traitement, Request $request): Response
    {
        $errors = [];

        $titre               = trim($request->request->get('titre', ''));
        $solution            = trim($request->request->get('solution', ''));
        $etapes              = trim($request->request->get('etapes', ''));
        $produitsRecommandes = trim($request->request->get('produitsRecommandes', ''));
        $conseilsPrevention  = trim($request->request->get('conseilsPrevention', ''));
        $dureeTraitement     = trim($request->request->get('dureeTraitement', ''));

        if (empty($titre)) {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (strlen($titre) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
        } elseif (strlen($titre) > 200) {
            $errors['titre'] = 'Le titre ne peut pas dépasser 200 caractères.';
        }

        if (empty($solution)) {
            $errors['solution'] = 'La solution est obligatoire.';
        } elseif (strlen($solution) < 10) {
            $errors['solution'] = 'La solution doit contenir au moins 10 caractères.';
        } elseif (strlen($solution) > 2000) {
            $errors['solution'] = 'La solution ne peut pas dépasser 2000 caractères.';
        }

        if (!empty($dureeTraitement) && strlen($dureeTraitement) > 100) {
            $errors['dureeTraitement'] = 'La durée ne peut pas dépasser 100 caractères.';
        }

        if (empty($errors)) {
            $traitement->setTitre($titre);
            $traitement->setSolution($solution);
            $traitement->setEtapes($etapes ?: null);
            $traitement->setProduitsRecommandes($produitsRecommandes ?: null);
            $traitement->setConseilsPrevention($conseilsPrevention ?: null);
            $traitement->setDureeTraitement($dureeTraitement ?: null);

            $this->em->flush();
            $this->addFlash('success', 'Solution "' . $titre . '" modifiée avec succès !');
        } else {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->redirectToRoute('admin_maladie_edit', ['id' => $traitement->getMaladie()->getId()]);
    }

    // ==================== SUPPRIMER SOLUTION ====================
    #[Route('/solution/delete/{id}', name: 'admin_solution_delete', methods: ['POST'])]
    public function deleteSolution(SolutionTraitement $traitement, Request $request): Response
    {
        $maladieId = $traitement->getMaladie()->getId();

        if ($this->isCsrfTokenValid('delete-solution' . $traitement->getId(), $request->request->get('_token'))) {
            $this->em->remove($traitement);
            $this->em->flush();
            $this->addFlash('success', 'Solution supprimée avec succès !');
        }

        return $this->redirectToRoute('admin_maladie_edit', ['id' => $maladieId]);
    }
}
