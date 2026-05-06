<?php

namespace App\Entity\Maladie;

use App\Entity\Trait\BlameableTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Entity\User\Utilisateur;
use App\Repository\Maladie\MaladieCaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaladieCaseRepository::class)]
#[ORM\Table(name: 'maladie_case')]
#[ORM\HasLifecycleCallbacks]
class MaladieCase
{
    use BlameableTrait;
    use TimestampableTrait { setCreatedAt as protected traitSetCreatedAt; }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Maladie::class)]
    #[ORM\JoinColumn(name: 'maladie_id', referencedColumnName: 'id_maladie', nullable: true, onDelete: 'CASCADE')]
    private ?Maladie $maladie = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $culture = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $parcelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $symptomes = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $statut = 'ouvert';

    #[ORM\Column(options: ['default' => true])]
    private bool $isPublic = true;

    /**
     * @var Collection<int, MaladieCaseUpdate>
     */
    #[ORM\OneToMany(mappedBy: 'case', targetEntity: MaladieCaseUpdate::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $updates;

    public function __construct()
    {
        $this->updates = new ArrayCollection();
        $this->traitSetCreatedAt(new \DateTimeImmutable());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaladie(): ?Maladie
    {
        return $this->maladie;
    }

    public function setMaladie(?Maladie $maladie): self
    {
        $this->maladie = $maladie;

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

    public function getCulture(): ?string
    {
        return $this->culture;
    }

    public function setCulture(?string $culture): self
    {
        $this->culture = $culture;

        return $this;
    }

    public function getParcelle(): ?string
    {
        return $this->parcelle;
    }

    public function setParcelle(?string $parcelle): self
    {
        $this->parcelle = $parcelle;

        return $this;
    }

    public function getSymptomes(): ?string
    {
        return $this->symptomes;
    }

    public function setSymptomes(string $symptomes): self
    {
        $this->symptomes = $symptomes;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function initializeTimestamp(): static
    {
        $this->traitSetCreatedAt(new \DateTimeImmutable());

        return $this;
    }

    /**
     * @return Collection<int, MaladieCaseUpdate>
     */
    public function getUpdates(): Collection
    {
        return $this->updates;
    }
}
