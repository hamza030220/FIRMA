<?php

namespace App\Repository\Maladie;

use App\Entity\Maladie\SolutionTraitementVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SolutionTraitementVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SolutionTraitementVote::class);
    }

    public function hasUserVoted(int $solutionId, int $userId): bool
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.solutionTraitement = :solutionId')
            ->andWhere('v.utilisateur = :userId')
            ->setParameter('solutionId', $solutionId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findVotesForUserByMaladie(int $userId, int $maladieId): array
    {
        return $this->createQueryBuilder('v')
            ->join('v.solutionTraitement', 's')
            ->andWhere('v.utilisateur = :userId')
            ->andWhere('s.maladie = :maladieId')
            ->setParameter('userId', $userId)
            ->setParameter('maladieId', $maladieId)
            ->getQuery()
            ->getResult();
    }
}
