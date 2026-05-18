<?php

namespace App\Entity\Maladie;

use App\Entity\Trait\BlameableTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Entity\User\Utilisateur;
use App\Repository\Maladie\SolutionTraitementVoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SolutionTraitementVoteRepository::class)]
#[ORM\Table(
    name: 'solution_traitement_vote',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_solution_user_vote', columns: ['solution_traitement_id', 'utilisateur_id'])
    ]
)]
#[ORM\HasLifecycleCallbacks]
class SolutionTraitementVote
{
    use BlameableTrait;
    use TimestampableTrait { setCreatedAt as protected traitSetCreatedAt; }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SolutionTraitement::class)]
    #[ORM\JoinColumn(name: 'solution_traitement_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?SolutionTraitement $solutionTraitement = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $type = null;

    public function __construct()
    {
        $this->traitSetCreatedAt(new \DateTimeImmutable());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSolutionTraitement(): ?SolutionTraitement
    {
        return $this->solutionTraitement;
    }

    public function setSolutionTraitement(?SolutionTraitement $solutionTraitement): self
    {
        $this->solutionTraitement = $solutionTraitement;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function initializeTimestamp(): static
    {
        $this->traitSetCreatedAt(new \DateTimeImmutable());

        return $this;
    }
}
