<?php

namespace App\Security;

use App\Entity\User\Utilisateur;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class BannedUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        if ($user->isBanned()) {
            // This message is carried through to the login page as an error,
            // but we redirect to a dedicated banned page via LoginFailureHandler.
            throw new CustomUserMessageAccountStatusException('BANNED');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Nothing needed post-auth
    }
}