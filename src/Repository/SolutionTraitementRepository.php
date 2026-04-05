<?php
// src/Repository/SolutionTraitementRepository.php

namespace App\Repository;

use App\Entity\SolutionTraitement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SolutionTraitementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolutionTraitement::class);
    }

    /**
     * Trouve les traitements par maladie
     */
    public function findByMaladieId(int $maladieId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.maladie = :maladieId')
            ->setParameter('maladieId', $maladieId)
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les traitements les plus utilisés
     */
    public function findMostUsed(int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de traitements
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}