<?php

namespace App\Repository\Forum;

use App\Entity\Forum\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return list<Post>
     */
    public function findForumFeed(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.utilisateur', 'u')
            ->addSelect('u')
            ->leftJoin('p.commentaires', 'c')
            ->addSelect('c')
            ->orderBy('p.dateCreation', 'DESC');

        if ($search !== null && $search !== '') {
            $qb
                ->andWhere('LOWER(p.titre) LIKE :term OR LOWER(p.contenu) LIKE :term OR LOWER(COALESCE(p.categorie, \'\')) LIKE :term')
                ->setParameter('term', '%' . mb_strtolower(trim($search)) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneForForum(int $id): ?Post
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.utilisateur', 'u')
            ->addSelect('u')
            ->leftJoin('p.commentaires', 'c')
            ->addSelect('c')
            ->leftJoin('c.utilisateur', 'cu')
            ->addSelect('cu')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countAllPosts(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function renameCategory(string $oldName, string $newName): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.categorie', ':newName')
            ->andWhere('p.categorie = :oldName')
            ->setParameter('newName', $newName)
            ->setParameter('oldName', $oldName)
            ->getQuery()
            ->execute();
    }

    public function clearCategory(string $categoryName): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.categorie', ':emptyValue')
            ->andWhere('p.categorie = :categoryName')
            ->setParameter('emptyValue', null)
            ->setParameter('categoryName', $categoryName)
            ->getQuery()
            ->execute();
    }
}
