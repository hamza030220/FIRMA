<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StaticPageController extends AbstractController
{
    #[Route('/aide', name: 'app_help_center')]
    public function helpCenter(): Response
    {
        return $this->render('static/help_center.html.twig');
    }

    #[Route('/conditions', name: 'app_terms')]
    public function terms(): Response
    {
        return $this->render('static/terms.html.twig');
    }

    #[Route('/confidentialite', name: 'app_privacy')]
    public function privacy(): Response
    {
        return $this->render('static/privacy.html.twig');
    }

    #[Route('/faq', name: 'app_faq')]
    public function faq(): Response
    {
        return $this->render('static/faq.html.twig');
    }
}
