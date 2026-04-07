<?php

namespace App\Entity\Marketplace;

use App\Repository\Marketplace\TerrainRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TerrainRepository::class)]
#[ORM\Table(name: 'terrains')]
class Terrain
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'terrains')]
    #[ORM\JoinColumn(name: 'categorie_id', nullable: false)]
    private ?Categorie $categorie = null;

    #[ORM\Column(length: 200)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'superficie_hectares', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $superficieHectares = null;

    #[ORM\Column(length: 100)]
    private ?string $ville = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(name: 'prix_mois', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prixMois = null;

    #[ORM\Column(name: 'prix_annee', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixAnnee = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $caution = '0.00';

    #[ORM\Column(length: 255, nullable: true, name: 'image_url')]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $disponible = true;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function setCategorie(?Categorie $categorie): static { $this->categorie = $categorie; return $this; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getSuperficieHectares(): ?string { return $this->superficieHectares; }
    public function setSuperficieHectares(string $superficieHectares): static { $this->superficieHectares = $superficieHectares; return $this; }

    public function getVille(): ?string { return $this->ville; }
    public function setVille(string $ville): static { $this->ville = $ville; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }

    public function getPrixMois(): ?string { return $this->prixMois; }
    public function setPrixMois(?string $prixMois): static { $this->prixMois = $prixMois; return $this; }

    public function getPrixAnnee(): ?string { return $this->prixAnnee; }
    public function setPrixAnnee(string $prixAnnee): static { $this->prixAnnee = $prixAnnee; return $this; }

    public function getCaution(): ?string { return $this->caution; }
    public function setCaution(?string $caution): static { $this->caution = $caution; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function isDisponible(): bool { return $this->disponible; }
    public function setDisponible(bool $disponible): static { $this->disponible = $disponible; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function __toString(): string { return $this->titre ?? ''; }
}
