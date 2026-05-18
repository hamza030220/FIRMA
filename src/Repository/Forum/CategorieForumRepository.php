<?php

namespace App\Repository\Forum;

use App\Entity\Forum\CategorieForum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieForum>
 */
class CategorieForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieForum::class);
    }

    /**
     * @return list<CategorieForum>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<string>
     */
    public function findCategoryNames(): array
    {
        return array_map(
            static fn (CategorieForum $categorie): string => (string) $categorie->getNom(),
            $this->findAllOrdered()
        );
    }

    public function findOneByNormalizedName(string $name): ?CategorieForum
    {
        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.nom) = :name')
            ->setParameter('name', mb_strtolower(trim($name)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
