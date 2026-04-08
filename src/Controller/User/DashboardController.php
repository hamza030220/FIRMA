<?php

namespace App\Controller\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

    #[Route('/techniciens', name: 'user_techniciens')]
    public function techniciens(): Response
    {
        return $this->render('user/tech/index.html.twig');
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
