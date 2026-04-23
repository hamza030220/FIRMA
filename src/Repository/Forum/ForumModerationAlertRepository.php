<?php

namespace App\Repository\Forum;

use App\Entity\Forum\ForumModerationAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ForumModerationAlert>
 */
class ForumModerationAlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumModerationAlert::class);
    }

    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<ForumModerationAlert>
     */
    public function findRecentPending(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.commentaire', 'c')->addSelect('c')
            ->leftJoin('a.utilisateur', 'u')->addSelect('u')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<ForumModerationAlert>
     */
    public function findRecentReviewed(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.commentaire', 'c')->addSelect('c')
            ->leftJoin('a.utilisateur', 'u')->addSelect('u')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'treated')
            ->orderBy('a.reviewedAt', 'DESC')
            ->addOrderBy('a.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<ForumModerationAlert>
     */
    public function findByCommentIds(array $commentIds): array
    {
        if ($commentIds === []) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->leftJoin('a.commentaire', 'c')->addSelect('c')
            ->leftJoin('a.utilisateur', 'u')->addSelect('u')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $commentIds)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
