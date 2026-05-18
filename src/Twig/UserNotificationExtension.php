<?php

namespace App\Twig;

use App\Entity\User\Utilisateur;
use App\Repository\User\UserNotificationRepository;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserNotificationExtension extends AbstractExtension
{
    public function __construct(
        private readonly UserNotificationRepository $notificationRepository,
        private readonly Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('user_notifications_count', [$this, 'getUnreadCount']),
            new TwigFunction('user_recent_notifications', [$this, 'getRecentNotifications']),
        ];
    }

    public function getUnreadCount(): int
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return 0;
        }

        try {
            return $this->notificationRepository->countUnreadForUser($user);
        } catch (DBALException) {
            return 0;
        }
    }

    /**
     * @return list<\App\Entity\User\UserNotification>
     */
    public function getRecentNotifications(int $limit = 5): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return [];
        }

        try {
            return $this->notificationRepository->findRecentForUser($user, $limit);
        } catch (DBALException) {
            return [];
        }
    }

    private function getCurrentUser(): ?Utilisateur
    {
        $user = $this->security->getUser();

        return $user instanceof Utilisateur ? $user : null;
    }
}
