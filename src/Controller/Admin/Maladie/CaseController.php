<?php

namespace App\Controller\Admin\Maladie;

use App\Entity\Maladie\MaladieCase;
use App\Repository\Maladie\MaladieCasePhotoRepository;
use App\Repository\Maladie\MaladieCaseRepository;
use App\Repository\Maladie\MaladieCaseUpdateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/maladie/cases')]
#[IsGranted('ROLE_ADMIN')]
class CaseController extends AbstractController
{
    #[Route('/', name: 'admin_maladie_case_index', methods: ['GET'])]
    public function index(
        MaladieCaseRepository $caseRepository,
        MaladieCaseUpdateRepository $updateRepository,
        MaladieCasePhotoRepository $photoRepository
    ): Response {
        $cases = $caseRepository->createQueryBuilder('c')
            ->leftJoin('c.maladie', 'm')->addSelect('m')
            ->leftJoin('c.utilisateur', 'u')->addSelect('u')
            ->leftJoin('c.updates', 'cu')->addSelect('cu')
            ->leftJoin('cu.photos', 'p')->addSelect('p')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $caseRows = [];
        foreach ($cases as $case) {
            $photoCount = 0;
            foreach ($case->getUpdates() as $update) {
                $photoCount += $update->getPhotos()->count();
            }

            $caseRows[] = [
                'case' => $case,
                'updateCount' => $case->getUpdates()->count(),
                'photoCount' => $photoCount,
            ];
        }

        $totalCases = (int) $caseRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $publicCases = (int) $caseRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.isPublic = 1')
            ->getQuery()
            ->getSingleScalarResult();

        $updateCount = (int) $updateRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $photoCount = (int) $photoRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/maladie/case_index.html.twig', [
            'caseRows' => $caseRows,
            'totalCases' => $totalCases,
            'publicCases' => $publicCases,
            'updateCount' => $updateCount,
            'photoCount' => $photoCount,
        ]);
    }

    #[Route('/{id}', name: 'admin_maladie_case_show', methods: ['GET'])]
    public function show(MaladieCase $case): Response
    {
        $photoCount = 0;
        foreach ($case->getUpdates() as $update) {
            $photoCount += $update->getPhotos()->count();
        }

        return $this->render('admin/maladie/case_show.html.twig', [
            'case' => $case,
            'updateCount' => $case->getUpdates()->count(),
            'photoCount' => $photoCount,
        ]);
    }
}
