<?php

namespace App\Repository\Maladie;

use App\Entity\Maladie\MaladieCase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MaladieCaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaladieCase::class);
    }

    /**
     * @return MaladieCase[]
     */
    public function findPublicCases(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
