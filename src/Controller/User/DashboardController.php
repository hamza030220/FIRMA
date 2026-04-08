<?php

namespace App\Controller\User;

use App\Repository\Maladie\MaladieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'user_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('user/dashboard.html.twig');
    }

    #[Route('/marketplace', name: 'user_marketplace')]
    public function marketplace(): Response
    {
        return $this->render('user/marketplace/index.html.twig');
    }

    #[Route('/forum', name: 'user_forum')]
    public function forum(): Response
    {
        return $this->render('user/forum/index.html.twig');
    }

    #[Route('/maladies', name: 'user_maladie')]
    public function maladie(Request $request, MaladieRepository $maladieRepository): Response
    {
        $keyword = $request->query->get('q', '');
        $tri     = $request->query->get('tri', 'nom');
        $gravite = $request->query->get('gravite', '');

        $maladies = $maladieRepository->searchAndFilter($keyword, $tri, $gravite);

        return $this->render('user/maladie/index.html.twig', [
            'maladies' => $maladies,
            'keyword'  => $keyword,
            'tri'      => $tri,
            'gravite'  => $gravite,
        ]);
    }

    #[Route('/evenements', name: 'user_evenements')]
    public function evenements(): Response
    {
        return $this->render('user/event/index.html.twig');
    }

    #[Route('/profil', name: 'user_profile')]
    public function profile(): Response
    {
        return $this->render('user/profile/index.html.twig');
    }
}
