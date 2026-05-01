<?php

namespace App\Repository\Marketplace;

use App\Entity\Marketplace\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * @param array<string, string> $orderBy
     * @return list<Commande>
     */
    public function findAllWithUser(array $orderBy = ['dateCommande' => 'DESC']): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.utilisateur', 'u')->addSelect('u');

        foreach ($orderBy as $field => $dir) {
            $qb->addOrderBy('c.' . $field, $dir);
        }

        /** @var list<Commande> */
        return $qb->getQuery()->getResult();
    }
}
