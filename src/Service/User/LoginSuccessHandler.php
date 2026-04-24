<?php

namespace App\Service\User;

use App\Entity\User\Utilisateur;
use App\Service\Maladie\Weather\MaladieWeatherAutoAlertService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router,
        private readonly MaladieWeatherAutoAlertService $autoAlertService,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        if ($user instanceof Utilisateur) {
            try {
                $this->autoAlertService->checkAndSendForUser($user);
            } catch (\Throwable) {
                // On ne bloque jamais la connexion si l'alerte météo échoue.
            }
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('admin_dashboard'));
        }

        return new RedirectResponse($this->router->generate('user_dashboard'));
    }
}
