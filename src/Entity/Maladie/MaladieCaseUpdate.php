<?php

namespace App\Entity\Maladie;

use App\Repository\Maladie\MaladieCaseUpdateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaladieCaseUpdateRepository::class)]
#[ORM\Table(name: 'maladie_case_update')]
#[ORM\HasLifecycleCallbacks]
class MaladieCaseUpdate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaladieCase::class, inversedBy: 'updates')]
    #[ORM\JoinColumn(name: 'case_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?MaladieCase $case = null;

    #[ORM\ManyToOne(targetEntity: SolutionTraitement::class)]
    #[ORM\JoinColumn(name: 'solution_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SolutionTraitement $solutionTraitement = null;

    #[ORM\Column(length: 30)]
    private ?string $resultat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, MaladieCasePhoto>
     */
    #[ORM\OneToMany(mappedBy: 'caseUpdate', targetEntity: MaladieCasePhoto::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $photos;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __construct()
    {
        $this->photos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCase(): ?MaladieCase
    {
        return $this->case;
    }

    public function setCase(?MaladieCase $case): self
    {
        $this->case = $case;
        return $this;
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

    public function getResultat(): ?string
    {
        return $this->resultat;
    }

    public function setResultat(string $resultat): self
    {
        $this->resultat = $resultat;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, MaladieCasePhoto>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }
}
