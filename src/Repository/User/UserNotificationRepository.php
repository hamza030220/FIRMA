<?php

namespace App\Repository\User;

use App\Entity\User\UserNotification;
use App\Entity\User\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserNotification>
 */
class UserNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserNotification::class);
    }

    public function countUnreadForUser(Utilisateur $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<UserNotification>
     */
    public function findRecentForUser(Utilisateur $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<UserNotification>
     */
    public function findUnreadForUser(Utilisateur $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, Utilisateur $user): ?UserNotification
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.id = :id')
            ->andWhere('n.recipient = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function markAllAsReadForUser(Utilisateur $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':read')
            ->set('n.readAt', ':readAt')
            ->andWhere('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('read', true)
            ->setParameter('readAt', new \DateTime('now', new \DateTimeZone('Africa/Lagos')))
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
