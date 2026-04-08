<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/marketplace', name: 'admin_marketplace')]
    public function marketplace(): Response
    {
        return $this->render('admin/marketplace/index.html.twig');
    }

    #[Route('/evenements', name: 'admin_evenements')]
    public function evenements(): Response
    {
        return $this->render('admin/event/index.html.twig');
    }

    #[Route('/techniciens', name: 'admin_techniciens')]
    public function techniciens(): Response
    {
        return $this->render('admin/tech/index.html.twig');
    }

    #[Route('/utilisateurs', name: 'admin_utilisateurs')]
    public function utilisateurs(): Response
    {
        return $this->render('admin/user/index.html.twig');
    }
}
