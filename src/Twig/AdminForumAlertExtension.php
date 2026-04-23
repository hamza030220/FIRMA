<?php

namespace App\Twig;

use App\Repository\Forum\ForumModerationAlertRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminForumAlertExtension extends AbstractExtension
{
    public function __construct(
        private readonly ForumModerationAlertRepository $moderationAlertRepository
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('forum_pending_alerts_count', [$this, 'getPendingAlertsCount']),
            new TwigFunction('forum_recent_alerts', [$this, 'getRecentAlerts']),
            new TwigFunction('forum_recent_reviewed_alerts', [$this, 'getRecentReviewedAlerts']),
        ];
    }

    public function getPendingAlertsCount(): int
    {
        return $this->moderationAlertRepository->countPending();
    }

    /**
     * @return list<\App\Entity\Forum\ForumModerationAlert>
     */
    public function getRecentAlerts(int $limit = 5): array
    {
        return $this->moderationAlertRepository->findRecentPending($limit);
    }

    /**
     * @return list<\App\Entity\Forum\ForumModerationAlert>
     */
    public function getRecentReviewedAlerts(int $limit = 5): array
    {
        return $this->moderationAlertRepository->findRecentReviewed($limit);
    }
}
