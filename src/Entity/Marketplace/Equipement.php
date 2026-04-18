<?php

namespace App\Entity\Marketplace;

use App\Repository\Marketplace\EquipementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EquipementRepository::class)]
#[ORM\Table(name: 'equipements')]
#[ORM\Index(columns: ['categorie_id'], name: 'idx_equip_categorie')]
#[ORM\Index(columns: ['fournisseur_id'], name: 'idx_equip_fournisseur')]
#[ORM\Index(columns: ['disponible'], name: 'idx_equip_disponible')]
#[ORM\Index(columns: ['date_creation'], name: 'idx_equip_date_creation')]
class Equipement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'equipements')]
    #[ORM\JoinColumn(name: 'categorie_id', nullable: false)]
    #[Assert\NotNull(message: 'La catégorie est obligatoire.')]
    private ?Categorie $categorie = null;

    #[ORM\ManyToOne(targetEntity: Fournisseur::class, inversedBy: 'equipements')]
    #[ORM\JoinColumn(name: 'fournisseur_id', nullable: false)]
    #[Assert\NotNull(message: 'Le fournisseur est obligatoire.')]
    private ?Fournisseur $fournisseur = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 200, maxMessage: 'Le nom ne peut pas dépasser 200 caractères.')]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'prix_achat', type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix d\'achat est obligatoire.')]
    #[Assert\Positive(message: 'Le prix d\'achat doit être supérieur à 0.')]
    private ?string $prixAchat = null;

    #[ORM\Column(name: 'prix_vente', type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix de vente est obligatoire.')]
    #[Assert\Positive(message: 'Le prix de vente doit être supérieur à 0.')]
    private ?string $prixVente = null;

    #[ORM\Column(name: 'quantite_stock')]
    #[Assert\PositiveOrZero(message: 'La quantité en stock ne peut pas être négative.')]
    private int $quantiteStock = 0;

    #[ORM\Column(name: 'seuil_alerte')]
    #[Assert\Positive(message: 'Le seuil d\'alerte doit être supérieur à 0.')]
    private int $seuilAlerte = 5;

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

    public function getFournisseur(): ?Fournisseur { return $this->fournisseur; }
    public function setFournisseur(?Fournisseur $fournisseur): static { $this->fournisseur = $fournisseur; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getPrixAchat(): ?string { return $this->prixAchat; }
    public function setPrixAchat(?string $prixAchat): static { $this->prixAchat = $prixAchat; return $this; }

    public function getPrixVente(): ?string { return $this->prixVente; }
    public function setPrixVente(?string $prixVente): static { $this->prixVente = $prixVente; return $this; }

    public function getQuantiteStock(): int { return $this->quantiteStock; }
    public function setQuantiteStock(?int $quantiteStock): static { $this->quantiteStock = $quantiteStock ?? 0; return $this; }

    public function getSeuilAlerte(): int { return $this->seuilAlerte; }
    public function setSeuilAlerte(?int $seuilAlerte): static { $this->seuilAlerte = $seuilAlerte ?? 0; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function isDisponible(): bool { return $this->disponible; }
    public function setDisponible(bool $disponible): static { $this->disponible = $disponible; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function __toString(): string { return $this->nom ?? ''; }
}
