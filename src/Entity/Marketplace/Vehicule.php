<?php

namespace App\Entity\Marketplace;

use App\Repository\Marketplace\VehiculeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
#[ORM\Table(name: 'vehicules')]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'vehicules')]
    #[ORM\JoinColumn(name: 'categorie_id', nullable: false)]
    private ?Categorie $categorie = null;

    #[ORM\Column(length: 200)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $marque = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $modele = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $immatriculation = null;

    #[ORM\Column(name: 'prix_jour', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixJour = null;

    #[ORM\Column(name: 'prix_semaine', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prixSemaine = null;

    #[ORM\Column(name: 'prix_mois', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $prixMois = null;

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

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getMarque(): ?string { return $this->marque; }
    public function setMarque(?string $marque): static { $this->marque = $marque; return $this; }

    public function getModele(): ?string { return $this->modele; }
    public function setModele(?string $modele): static { $this->modele = $modele; return $this; }

    public function getImmatriculation(): ?string { return $this->immatriculation; }
    public function setImmatriculation(?string $immatriculation): static { $this->immatriculation = $immatriculation; return $this; }

    public function getPrixJour(): ?string { return $this->prixJour; }
    public function setPrixJour(string $prixJour): static { $this->prixJour = $prixJour; return $this; }

    public function getPrixSemaine(): ?string { return $this->prixSemaine; }
    public function setPrixSemaine(?string $prixSemaine): static { $this->prixSemaine = $prixSemaine; return $this; }

    public function getPrixMois(): ?string { return $this->prixMois; }
    public function setPrixMois(?string $prixMois): static { $this->prixMois = $prixMois; return $this; }

    public function getCaution(): ?string { return $this->caution; }
    public function setCaution(?string $caution): static { $this->caution = $caution; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function isDisponible(): bool { return $this->disponible; }
    public function setDisponible(bool $disponible): static { $this->disponible = $disponible; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function __toString(): string { return $this->nom ?? ''; }
}
