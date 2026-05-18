<?php

namespace App\Repository\Maladie;

use App\Entity\Maladie\MaladieCasePhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaladieCasePhoto>
 */
class MaladieCasePhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaladieCasePhoto::class);
    }
}
