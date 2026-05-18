<?php

namespace App\Repository\Maladie;

use App\Entity\Maladie\MaladieCaseUpdate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaladieCaseUpdate>
 */
class MaladieCaseUpdateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaladieCaseUpdate::class);
    }
}
