<?php

namespace App\Controller\User;

use App\Repository\User\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BannedController extends AbstractController
{
    #[Route('/compte-suspendu', name: 'user_banned')]
    public function banned(Request $request, UtilisateurRepository $repo): Response
    {
        // If a logged-in user tries to reach this page, log them out first.
        // (Normally they can't log in if banned, but just in case.)
        $utilisateur = null;
        $email = $request->getSession()->get('banned_email');

        if ($email) {
            $utilisateur = $repo->findOneBy(['email' => $email]);
        }

        return $this->render('user/banned.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }
}