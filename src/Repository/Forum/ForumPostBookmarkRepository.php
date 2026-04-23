<?php

namespace App\Repository\Forum;

use App\Entity\Forum\ForumPostBookmark;
use App\Entity\Forum\Post;
use App\Entity\User\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ForumPostBookmark>
 */
class ForumPostBookmarkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumPostBookmark::class);
    }

    public function findOneByPostUserType(Post $post, Utilisateur $user, string $bookmarkType): ?ForumPostBookmark
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.post = :post')
            ->andWhere('b.utilisateur = :user')
            ->andWhere('b.bookmarkType = :bookmarkType')
            ->setParameter('post', $post)
            ->setParameter('user', $user)
            ->setParameter('bookmarkType', $bookmarkType)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<int>
     */
    public function findPostIdsByUserAndType(Utilisateur $user, string $bookmarkType): array
    {
        $rows = $this->createQueryBuilder('b')
            ->select('IDENTITY(b.post) AS postId')
            ->andWhere('b.utilisateur = :user')
            ->andWhere('b.bookmarkType = :bookmarkType')
            ->setParameter('user', $user)
            ->setParameter('bookmarkType', $bookmarkType)
            ->orderBy('b.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $postIds = [];
        foreach ($rows as $row) {
            if (isset($row['postId'])) {
                $postIds[] = (int) $row['postId'];
            }
        }

        return array_values(array_unique($postIds));
    }

    /**
     * @return list<Post>
     */
    public function findPostsByUserAndType(Utilisateur $user, string $bookmarkType): array
    {
        $bookmarks = $this->createQueryBuilder('b')
            ->select('b, p, u, c, cu, r, ru')
            ->innerJoin('b.post', 'p')
            ->leftJoin('p.utilisateur', 'u')
            ->addSelect('u')
            ->leftJoin('p.commentaires', 'c')
            ->addSelect('c')
            ->leftJoin('c.utilisateur', 'cu')
            ->addSelect('cu')
            ->leftJoin('p.reactions', 'r')
            ->addSelect('r')
            ->leftJoin('r.utilisateur', 'ru')
            ->addSelect('ru')
            ->andWhere('b.utilisateur = :user')
            ->andWhere('b.bookmarkType = :bookmarkType')
            ->setParameter('user', $user)
            ->setParameter('bookmarkType', $bookmarkType)
            ->orderBy('b.createdAt', 'DESC')
            ->addOrderBy('p.isPinned', 'DESC')
            ->addOrderBy('p.dateCreation', 'DESC')
            ->distinct()
            ->getQuery()
            ->getResult();

        $posts = [];

        foreach ($bookmarks as $bookmark) {
            if (!$bookmark instanceof ForumPostBookmark) {
                continue;
            }

            $post = $bookmark->getPost();
            if (!$post instanceof Post) {
                continue;
            }

            $postId = $post->getId();
            if ($postId === null) {
                continue;
            }

            $posts[$postId] = $post;
        }

        return array_values($posts);
    }
}
