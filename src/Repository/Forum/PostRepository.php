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
    public function findForumFeed(?string $search = null, string $sort = 'recent'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.utilisateur', 'u')
            ->addSelect('u')
            ->leftJoin('p.commentaires', 'c')
            ->addSelect('c')
            ->leftJoin('c.utilisateur', 'cu')
            ->addSelect('cu')
            ->leftJoin('p.reactions', 'r')
            ->addSelect('r')
            ->leftJoin('r.utilisateur', 'ru')
            ->addSelect('ru');

        if ($search !== null && $search !== '') {
            $qb
                ->andWhere(
                    'LOWER(p.titre) LIKE :term'
                )
                ->setParameter('term', '%' . mb_strtolower(trim($search)) . '%');
        }

        switch ($sort) {
            case 'oldest':
                $qb
                    ->orderBy('p.isPinned', 'DESC')
                    ->addOrderBy('p.dateCreation', 'ASC');
                break;
            case 'title_asc':
                $qb
                    ->orderBy('p.isPinned', 'DESC')
                    ->addOrderBy('p.titre', 'ASC')
                    ->addOrderBy('p.dateCreation', 'DESC');
                break;
            case 'title_desc':
                $qb
                    ->orderBy('p.isPinned', 'DESC')
                    ->addOrderBy('p.titre', 'DESC')
                    ->addOrderBy('p.dateCreation', 'DESC');
                break;
            case 'recent':
            default:
                $qb
                    ->orderBy('p.isPinned', 'DESC')
                    ->addOrderBy('p.dateCreation', 'DESC');
                break;
        }

        return $qb
            ->distinct()
            ->getQuery()
            ->getResult();
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
            ->leftJoin('p.reactions', 'r')
            ->addSelect('r')
            ->leftJoin('r.utilisateur', 'ru')
            ->addSelect('ru')
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

    public function countCategorizedPosts(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.categorie IS NOT NULL')
            ->andWhere('p.categorie <> :emptyValue')
            ->setParameter('emptyValue', '')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUncategorizedPosts(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.categorie IS NULL OR p.categorie = :emptyValue')
            ->setParameter('emptyValue', '')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{name: string, count: int}|null
     */
    public function findMostUsedCategory(): ?array
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.categorie AS name')
            ->addSelect('COUNT(p.id) AS usageCount')
            ->andWhere('p.categorie IS NOT NULL')
            ->andWhere('p.categorie <> :emptyValue')
            ->setParameter('emptyValue', '')
            ->groupBy('p.categorie')
            ->orderBy('usageCount', 'DESC')
            ->addOrderBy('p.categorie', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($result) || !isset($result['name'], $result['usageCount'])) {
            return null;
        }

        return [
            'name' => (string) $result['name'],
            'count' => (int) $result['usageCount'],
        ];
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
