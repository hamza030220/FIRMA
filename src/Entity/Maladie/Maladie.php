<?php
// src/Entity/Maladie.php

namespace App\Entity\Maladie;
use App\Repository\Maladie\MaladieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaladieRepository::class)]
#[ORM\Table(name: "maladie")]
#[ORM\HasLifecycleCallbacks]
class Maladie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
#[ORM\Column(name: "id_maladie", type: 'integer')] 
    private ?int $id = null;

    #[ORM\Column(name: "nom", length: 150)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 3, minMessage: 'Le nom doit contenir au moins 3 caractères.', max: 150, maxMessage: 'Le nom ne peut pas dépasser 150 caractères.')]
    private ?string $nom = null;

    #[ORM\Column(name: "nom_scientifique", length: 200, nullable: true)]
    private ?string $nomScientifique = null;

    #[ORM\Column(name: "description", type: 'text', nullable: true)]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(min: 10, minMessage: 'La description doit contenir au moins 10 caractères.')]
    private ?string $description = null;

    #[ORM\Column(name: "symptomes", type: 'text', nullable: true)]
    #[Assert\NotBlank(message: 'Les symptômes sont obligatoires.')]
    #[Assert\Length(min: 10, minMessage: 'Les symptômes doivent contenir au moins 10 caractères.')]
    private ?string $symptomes = null;

    #[ORM\Column(name: "image_url", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: "niveau_gravite", length: 20, nullable: true)]
    private ?string $niveauGravite = 'moyen';

    #[ORM\Column(name: "saison_frequente", length: 100, nullable: true)]
    private ?string $saisonFrequente = null;

    // Seuils meteo stockes en base pour le diagnostic.
    #[ORM\Column(name: "temp_min", type: 'float', nullable: true)]
    private ?float $tempMin = null;

    #[ORM\Column(name: "temp_max", type: 'float', nullable: true)]
    private ?float $tempMax = null;

    #[ORM\Column(name: "humidite_min", type: 'integer', nullable: true)]
    private ?int $humiditeMin = null;

    #[ORM\Column(name: "created_by", nullable: true)]
    private ?int $createdBy = null;

    #[ORM\Column(name: "created_at")]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: "updated_at", nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'maladie', targetEntity: SolutionTraitement::class, cascade: ['remove'])]
    private Collection $solutionTraitements;

    public function __construct()
    {
        $this->solutionTraitements = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ============ GETTERS & SETTERS ============
    
    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getNomScientifique(): ?string { return $this->nomScientifique; }
    public function setNomScientifique(?string $nomScientifique): self { $this->nomScientifique = $nomScientifique; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getSymptomes(): ?string { return $this->symptomes; }
    public function setSymptomes(?string $symptomes): self { $this->symptomes = $symptomes; return $this; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): self { $this->imageUrl = $imageUrl; return $this; }
    public function getNiveauGravite(): ?string { return $this->niveauGravite; }
    public function setNiveauGravite(?string $niveauGravite): self { $this->niveauGravite = $niveauGravite; return $this; }
    public function getSaisonFrequente(): ?string { return $this->saisonFrequente; }
    public function setSaisonFrequente(?string $saisonFrequente): self { $this->saisonFrequente = $saisonFrequente; return $this; }
    public function getTempMin(): ?float { return $this->tempMin; }
    public function setTempMin(?float $tempMin): self { $this->tempMin = $tempMin; return $this; }
    public function getTempMax(): ?float { return $this->tempMax; }
    public function setTempMax(?float $tempMax): self { $this->tempMax = $tempMax; return $this; }
    public function getHumiditeMin(): ?int { return $this->humiditeMin; }
    public function setHumiditeMin(?int $humiditeMin): self { $this->humiditeMin = $humiditeMin; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function getSolutionTraitements(): Collection { return $this->solutionTraitements; }
}
