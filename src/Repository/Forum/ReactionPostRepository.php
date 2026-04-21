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
}
