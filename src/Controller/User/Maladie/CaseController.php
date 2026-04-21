<?php

namespace App\Controller\User\Maladie;

use App\Entity\Maladie\MaladieCase;
use App\Entity\Maladie\MaladieCaseUpdate;
use App\Entity\Maladie\MaladieCasePhoto;
use App\Entity\Maladie\Maladie;
use App\Entity\Maladie\SolutionTraitement;
use App\Entity\User\Utilisateur;
use App\Repository\Maladie\MaladieCaseRepository;
use App\Repository\Maladie\MaladieRepository;
use App\Repository\Maladie\SolutionTraitementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/maladies/suivi')]
#[IsGranted('ROLE_USER')]
class CaseController extends AbstractController
{
    private const ALLOWED_RESULTATS = [
        'succes',
        'amelioration',
        'stable',
        'echec',
    ];

    private const MAX_PHOTO_SIZE_BYTES = 5_000_000;

    #[Route('', name: 'user_maladie_case_index')]
    public function index(MaladieCaseRepository $caseRepository): Response
    {
        return $this->render('user/maladie/case_index.html.twig', [
            'cases' => $caseRepository->findPublicCases(),
        ]);
    }

    #[Route('/new', name: 'user_maladie_case_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        MaladieRepository $maladieRepository,
        EntityManagerInterface $em
    ): Response {
        $errors = [];
        $maladies = $maladieRepository->findAll();

        if ($request->isMethod('POST')) {
            $maladieId = (int) $request->request->get('maladie_id');
            $culture = trim((string) $request->request->get('culture', ''));
            $parcelle = trim((string) $request->request->get('parcelle', ''));
            $symptomes = trim((string) $request->request->get('symptomes', ''));
            $isPublic = $request->request->get('is_public') === '1';

            if ($maladieId <= 0) {
                $errors['maladie'] = 'Veuillez choisir une maladie.';
            }
            if ($symptomes === '' || mb_strlen($symptomes) < 10) {
                $errors['symptomes'] = 'Les symptomes doivent contenir au moins 10 caracteres.';
            }

            $maladie = $maladieId > 0 ? $maladieRepository->find($maladieId) : null;
            if (!$maladie instanceof Maladie) {
                $errors['maladie'] = 'Maladie invalide.';
            }

            if ($errors === []) {
                $user = $this->getUser();
                if (!$user instanceof Utilisateur) {
                    throw $this->createAccessDeniedException();
                }

                $case = new MaladieCase();
                $case->setMaladie($maladie);
                $case->setUtilisateur($user);
                $case->setCulture($culture ?: null);
                $case->setParcelle($parcelle ?: null);
                $case->setSymptomes($symptomes);
                $case->setIsPublic($isPublic);
                $case->setStatut('ouvert');

                $em->persist($case);
                $em->flush();

                return $this->redirectToRoute('user_maladie_case_show', ['id' => $case->getId()]);
            }
        }

        return $this->render('user/maladie/case_new.html.twig', [
            'maladies' => $maladies,
            'errors' => $errors,
        ]);
    }

    #[Route('/{id}', name: 'user_maladie_case_show', methods: ['GET'])]
    public function show(MaladieCase $case, SolutionTraitementRepository $solutionRepo): Response
    {
        $solutions = $solutionRepo->findByMaladieId((int) $case->getMaladie()?->getId());

        return $this->render('user/maladie/case_show.html.twig', [
            'case' => $case,
            'solutions' => $solutions,
        ]);
    }

    #[Route('/{id}/update', name: 'user_maladie_case_update', methods: ['POST'])]
    public function addUpdate(
        MaladieCase $case,
        Request $request,
        SolutionTraitementRepository $solutionRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur || $case->getUtilisateur()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $resultat = trim((string) $request->request->get('resultat', ''));
        $commentaire = trim((string) $request->request->get('commentaire', ''));
        $solutionId = (int) $request->request->get('solution_id');
        $errors = [];
        $formData = [
            'resultat' => $resultat,
            'commentaire' => $commentaire,
            'solution_id' => $solutionId,
        ];

        if ($resultat === '' || !in_array($resultat, self::ALLOWED_RESULTATS, true)) {
            $errors['resultat'] = 'Veuillez choisir un resultat valide.';
        }

        if ($commentaire !== '' && mb_strlen($commentaire) > 500) {
            $errors['commentaire'] = 'Le commentaire est trop long (500 caracteres max).';
        }

        $solution = null;
        if ($solutionId > 0) {
            $solution = $solutionRepo->find($solutionId);
            if (!$solution instanceof SolutionTraitement) {
                $errors['solution'] = 'Traitement invalide.';
            }
        }

        $files = $request->files->all('photos');
        if ($files !== []) {
            foreach ($files as $file) {
                if (!$file) {
                    continue;
                }

                $extension = strtolower((string) $file->getClientOriginalExtension());
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $errors['photos'] = 'Formats autorises: JPG, PNG, WEBP.';
                    break;
                }

                if ($file->getSize() > self::MAX_PHOTO_SIZE_BYTES) {
                    $errors['photos'] = 'Chaque photo doit faire 5 Mo maximum.';
                    break;
                }
            }
        }

        if ($errors !== []) {
            $solutions = $solutionRepo->findByMaladieId((int) $case->getMaladie()?->getId());

            return $this->render('user/maladie/case_show.html.twig', [
                'case' => $case,
                'solutions' => $solutions,
                'errors' => $errors,
                'formData' => $formData,
            ]);
        }

        $update = new MaladieCaseUpdate();
        $update->setCase($case);
        $update->setResultat($resultat);
        $update->setCommentaire($commentaire ?: null);

        if ($solution instanceof SolutionTraitement) {
            $update->setSolutionTraitement($solution);
        }

        if ($files !== []) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/maladies/cases';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            foreach ($files as $file) {
                if (!$file) {
                    continue;
                }

                $extension = strtolower((string) $file->getClientOriginalExtension());
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    continue;
                }

                $newFilename = 'case_' . $case->getId() . '_' . uniqid() . '.' . $extension;
                try {
                    $file->move($uploadDir, $newFilename);
                    $photo = new MaladieCasePhoto();
                    $photo->setCaseUpdate($update);
                    $photo->setFilename($newFilename);
                    $em->persist($photo);
                } catch (FileException $e) {
                    // Ignore failed upload to keep update creation.
                }
            }
        }

        $em->persist($update);
        $em->flush();

        return $this->redirectToRoute('user_maladie_case_show', ['id' => $case->getId()]);
    }
}
