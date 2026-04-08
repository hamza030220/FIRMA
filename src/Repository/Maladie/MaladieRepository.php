<?php

namespace App\Repository\Maladie;

use App\Entity\Maladie\Maladie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function searchAndFilter(string $keyword = '', string $tri = 'nom', string $gravite = ''): array
    {
        $qb = $this->createQueryBuilder('m');

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
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.nom)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
