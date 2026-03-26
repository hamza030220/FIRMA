<?php

namespace App\Repository\Marketplace;

use App\Entity\Marketplace\Categorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Categorie>
 */
class CategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categorie::class);
    }

    /** @return Categorie[] */
    public function findByType(string $type): array
    {
        return $this->findBy(['typeProduit' => $type], ['nom' => 'ASC']);
    }
}
