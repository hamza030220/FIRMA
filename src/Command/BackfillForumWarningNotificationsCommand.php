<?php

namespace App\Command;

use App\Entity\Forum\ForumModerationAlert;
use App\Entity\User\UserNotification;
use App\Repository\Forum\ForumModerationAlertRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:forum:backfill-warning-notifications',
    description: 'Creer les notifications internes manquantes pour les anciens avertissements du forum.'
)]
class BackfillForumWarningNotificationsCommand extends Command
{
    private const WARNING_NOTE_PREFIX = 'Avertissement envoye le ';

    public function __construct(
        private readonly ForumModerationAlertRepository $alertRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $alerts = $this->alertRepository->findAll();
        $created = 0;
        $skipped = 0;

        foreach ($alerts as $alert) {
            if (!$alert instanceof ForumModerationAlert) {
                continue;
            }

            $note = trim((string) $alert->getNote());
            if (!str_starts_with($note, self::WARNING_NOTE_PREFIX)) {
                continue;
            }

            $user = $alert->getUtilisateur();
            if ($user === null || $user->getId() === null) {
                $skipped++;
                continue;
            }

            $post = $alert->getCommentaire()?->getPost();
            $linkUrl = $post !== null && $post->getId() !== null
                ? '/user/forum/' . $post->getId() . '#post-comments'
                : '/user/notifications';

            $title = 'Avertissement forum';
            $message = 'Notre application interdit les propos insultants. Consultez vos avertissements dans le forum.';

            $createdAt = $this->parseCreatedAtFromNote($note) ?? $alert->getCreatedAt() ?? new \DateTime('now', new \DateTimeZone('Africa/Lagos'));
            if ($createdAt instanceof \DateTimeImmutable) {
                $createdAt = \DateTime::createFromInterface($createdAt);
            }

            $alert
                ->setStatus('treated')
                ->setReviewedAt($createdAt);

            if ($this->notificationAlreadyExists($user->getId(), $title, $message, $linkUrl)) {
                $skipped++;
                continue;
            }

            $notification = new UserNotification();
            $notification
                ->setRecipient($user)
                ->setType('forum_warning')
                ->setTitle($title)
                ->setMessage($message)
                ->setLinkUrl($linkUrl)
                ->setIsRead(false)
                ->setCreatedAt($createdAt)
                ->setReadAt(null);

            $this->entityManager->persist($notification);
            $created++;
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Rattrapage termine: %d notification%s creee%s, %d avertissement%s ignore%s.',
            $created,
            $created > 1 ? 's' : '',
            $created > 1 ? 's' : '',
            $skipped,
            $skipped > 1 ? 's' : '',
            $skipped > 1 ? 's' : ''
        ));

        return Command::SUCCESS;
    }

    private function notificationAlreadyExists(int $recipientId, string $title, string $message, string $linkUrl): bool
    {
        $count = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(n.id)')
            ->from(UserNotification::class, 'n')
            ->andWhere('IDENTITY(n.recipient) = :recipientId')
            ->andWhere('n.type = :type')
            ->andWhere('n.title = :title')
            ->andWhere('n.message = :message')
            ->andWhere('(n.linkUrl = :linkUrl OR (n.linkUrl IS NULL AND :linkUrl IS NULL))')
            ->setParameter('recipientId', $recipientId)
            ->setParameter('type', 'forum_warning')
            ->setParameter('title', $title)
            ->setParameter('message', $message)
            ->setParameter('linkUrl', $linkUrl)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    private function parseCreatedAtFromNote(string $note): ?\DateTime
    {
        if (!str_starts_with($note, self::WARNING_NOTE_PREFIX)) {
            return null;
        }

        $rawDate = trim(substr($note, strlen(self::WARNING_NOTE_PREFIX)));
        $date = \DateTime::createFromFormat('d/m/Y H:i', $rawDate, new \DateTimeZone('Africa/Lagos'));

        return $date ?: null;
    }
}
