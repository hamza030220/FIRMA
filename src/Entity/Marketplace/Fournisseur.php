<?php

namespace App\Entity\Marketplace;

use App\Repository\Marketplace\FournisseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FournisseurRepository::class)]
#[ORM\Table(name: 'fournisseurs')]
class Fournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200, name: 'nom_entreprise')]
    private ?string $nomEntreprise = null;

    #[ORM\Column(length: 100, nullable: true, name: 'contact_nom')]
    private ?string $contactNom = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $actif = true;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    /** @var Collection<int, Equipement> */
    #[ORM\OneToMany(targetEntity: Equipement::class, mappedBy: 'fournisseur')]
    private Collection $equipements;

    public function __construct()
    {
        $this->equipements = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getNomEntreprise(): ?string { return $this->nomEntreprise; }
    public function setNomEntreprise(string $nomEntreprise): static { $this->nomEntreprise = $nomEntreprise; return $this; }

    public function getContactNom(): ?string { return $this->contactNom; }
    public function setContactNom(?string $contactNom): static { $this->contactNom = $contactNom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }

    public function getVille(): ?string { return $this->ville; }
    public function setVille(?string $ville): static { $this->ville = $ville; return $this; }

    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $actif): static { $this->actif = $actif; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    /** @return Collection<int, Equipement> */
    public function getEquipements(): Collection { return $this->equipements; }

    public function __toString(): string { return $this->nomEntreprise ?? ''; }
}
