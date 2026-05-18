<?php

namespace App\Repository\Forum;

use App\Entity\Forum\Post;
use App\Entity\Forum\ReactionPost;
use App\Entity\User\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReactionPost>
 */
class ReactionPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReactionPost::class);
    }

    public function findOneByPostAndUser(Post $post, Utilisateur $user): ?ReactionPost
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.post = :post')
            ->andWhere('r.utilisateur = :user')
            ->setParameter('post', $post)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<int> $postIds
     * @return array<int, string>
     */
    public function findUserReactionTypesByPostIds(?Utilisateur $user, array $postIds): array
    {
        if ($user === null || $user->getId() === null || $postIds === []) {
            return [];
        }

        $rows = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('IDENTITY(r.post) AS postId, r.type AS reactionType')
            ->from(ReactionPost::class, 'r')
            ->andWhere('r.utilisateur = :user')
            ->andWhere('IDENTITY(r.post) IN (:postIds)')
            ->setParameter('user', $user)
            ->setParameter('postIds', $postIds)
            ->getQuery()
            ->getArrayResult();

        $reactions = [];
        foreach ($rows as $row) {
            if (!isset($row['postId'], $row['reactionType'])) {
                continue;
            }

            $reactions[(int) $row['postId']] = (string) $row['reactionType'];
        }

        return $reactions;
    }
}
