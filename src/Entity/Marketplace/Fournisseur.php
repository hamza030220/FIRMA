<?php

namespace App\Entity\Marketplace;

use App\Repository\Marketplace\FournisseurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FournisseurRepository::class)]
#[ORM\Table(name: 'fournisseurs')]
class Fournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200, name: 'nom_entreprise')]
    #[Assert\NotBlank(message: 'Le nom de l\'entreprise est obligatoire.')]
    #[Assert\Length(min: 2, max: 200, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $nomEntreprise = null;

    #[ORM\Column(length: 100, nullable: true, name: 'contact_nom')]
    #[Assert\NotBlank(message: 'Le nom du contact est obligatoire.')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom du contact doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom du contact ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $contactNom = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'adresse email n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire.')]
    #[Assert\Regex(pattern: '/^\+?[\d\s\-()]{8,20}$/', message: 'Le numéro de téléphone n\'est pas valide (8 à 20 chiffres).')]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(message: 'L\'adresse est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $adresse = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'La ville est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.')]
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
    public function setNomEntreprise(?string $nomEntreprise): static { $this->nomEntreprise = $nomEntreprise; return $this; }

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
