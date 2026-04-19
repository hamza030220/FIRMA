<?php

namespace App\Service\User;

use App\Repository\User\UtilisateurRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private RouterInterface $router,
        private UtilisateurRepository $utilisateurRepository,
    ) {}

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        // Detect our BANNED marker
        if (
            $exception instanceof CustomUserMessageAccountStatusException
            && $exception->getMessageKey() === 'BANNED'
        ) {
            // Pass the email so the banned page can show the reason
            $email = $request->request->get('_username', '');
            $request->getSession()->set('banned_email', $email);

            return new RedirectResponse($this->router->generate('user_banned'));
        }

        // Default: back to login with error
        return new RedirectResponse($this->router->generate('app_login'));
    }
}