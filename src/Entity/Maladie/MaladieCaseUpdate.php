<?php

namespace App\Entity\Maladie;

use App\Entity\Trait\BlameableTrait;
use App\Entity\Trait\TimestampableTrait;
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
    use BlameableTrait;
    use TimestampableTrait { setCreatedAt as protected traitSetCreatedAt; }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaladieCase::class, inversedBy: 'updates')]
    #[ORM\JoinColumn(name: 'case_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?MaladieCase $case = null;

    #[ORM\ManyToOne(targetEntity: SolutionTraitement::class)]
    #[ORM\JoinColumn(name: 'solution_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SolutionTraitement $solutionTraitement = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $resultat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    /**
     * @var Collection<int, MaladieCasePhoto>
     */
    #[ORM\OneToMany(mappedBy: 'caseUpdate', targetEntity: MaladieCasePhoto::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $photos;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->traitSetCreatedAt(new \DateTimeImmutable());
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function initializeTimestamp(): static
    {
        $this->traitSetCreatedAt(new \DateTimeImmutable());

        return $this;
    }

    /**
     * @return Collection<int, MaladieCasePhoto>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }
}
