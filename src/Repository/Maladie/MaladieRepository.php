<?php

namespace App\Repository\Maladie;

use App\Entity\Maladie\Maladie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Maladie>
 */
class MaladieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maladie::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{items: list<Maladie>, total: int}
     */
    public function findPaginatedForUserList(
        string $keyword = '',
        string $tri = 'nom',
        string $gravite = '',
        int $page = 1,
        int $limit = 9
    ): array {
        $qb = $this->createQueryBuilder('m')
            ->distinct()
            ->leftJoin('m.solutionTraitements', 's')
            ->addSelect('s');

        $keyword = trim($keyword);
        if ($keyword !== '') {
            $qb->andWhere('LOWER(COALESCE(m.nom, \'\')) LIKE :keyword
                OR LOWER(COALESCE(m.description, \'\')) LIKE :keyword
                OR LOWER(COALESCE(m.symptomes, \'\')) LIKE :keyword')
                ->setParameter('keyword', '%' . mb_strtolower($keyword) . '%');
        }

        if ($gravite !== '') {
            $qb->andWhere('m.niveauGravite = :gravite')
                ->setParameter('gravite', $gravite);
        }

        switch ($tri) {
            case 'gravite':
                $qb->addSelect("CASE
                        WHEN m.niveauGravite = 'critique' THEN 1
                        WHEN m.niveauGravite = 'eleve' THEN 2
                        WHEN m.niveauGravite = 'moyen' THEN 3
                        ELSE 4
                    END AS HIDDEN gravite_order")
                    ->orderBy('gravite_order', 'ASC')
                    ->addOrderBy('m.nom', 'ASC');
                break;
            case 'saison':
                $qb->orderBy('m.saisonFrequente', 'ASC')
                    ->addOrderBy('m.nom', 'ASC');
                break;
            default:
                $qb->orderBy('m.nom', 'ASC');
        }

        $query = $qb->getQuery()
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit);

        $paginator = new Paginator($query, true);

        return [
            'items' => array_values(iterator_to_array($paginator)),
            'total' => count($paginator),
        ];
    }

    /**
     * @return array<int, Maladie>
     */
    public function searchAndFilter(string $keyword = '', string $tri = 'nom', string $gravite = ''): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.solutionTraitements', 's')
            ->addSelect('s');

        if (!empty($keyword)) {
            $qb->andWhere('LOWER(m.nom) LIKE :keyword 
                OR LOWER(m.description) LIKE :keyword 
                OR LOWER(m.symptomes) LIKE :keyword')
               ->setParameter('keyword', '%' . strtolower(trim($keyword)) . '%');
        }

        if (!empty($gravite)) {
            $qb->andWhere('m.niveauGravite = :gravite')
               ->setParameter('gravite', $gravite);
        }

        switch ($tri) {
            case 'gravite':
                $qb->addSelect("CASE 
                        WHEN m.niveauGravite = 'critique' THEN 1
                        WHEN m.niveauGravite = 'eleve' THEN 2
                        WHEN m.niveauGravite = 'moyen' THEN 3
                        ELSE 4
                    END AS HIDDEN gravite_order")
                   ->orderBy('gravite_order', 'ASC');
                break;
            case 'saison':
                $qb->orderBy('m.saisonFrequente', 'ASC');
                break;
            default:
                $qb->orderBy('m.nom', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Maladie>
     */
    public function searchByKeyword(string $keyword): array
    {
        $keyword = '%' . strtolower(trim($keyword)) . '%';
        return $this->createQueryBuilder('m')
            ->where('LOWER(m.nom) LIKE :keyword')
            ->orWhere('LOWER(m.description) LIKE :keyword')
            ->setParameter('keyword', $keyword)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.nom)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneByNormalizedName(string $name, ?int $ignoreId = null): ?Maladie
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('LOWER(m.nom) = :name')
            ->setParameter('name', mb_strtolower(trim($name)))
            ->setMaxResults(1);

        if ($ignoreId !== null) {
            $qb->andWhere('m.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
