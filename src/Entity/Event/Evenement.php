<?php

namespace App\Entity\Event;

use App\Repository\Event\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[ORM\Table(name: 'evenements')]
#[ORM\HasLifecycleCallbacks]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_evenement')]
    private ?int $idEvenement = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est requis')]
    #[Assert\Length(min: 3, max: 100, minMessage: 'Le titre doit contenir au moins {{ limit }} caractères', maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères')]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'image_url', length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(name: 'type_evenement', length: 50)]
    #[Assert\NotBlank(message: 'Le type est requis')]
    private ?string $typeEvenement = null;

    #[ORM\Column(name: 'date_debut', type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date de début est requise')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date de fin est requise')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: 'horaire_debut', type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'horaire de début est requis")]
    private ?\DateTimeInterface $horaireDebut = null;

    #[ORM\Column(name: 'horaire_fin', type: Types::TIME_MUTABLE)]
    #[Assert\NotNull(message: "L'horaire de fin est requis")]
    private ?\DateTimeInterface $horaireFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Le lieu ne peut pas dépasser {{ limit }} caractères')]
    private ?string $lieu = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères')]
    private ?string $adresse = null;

    #[ORM\Column(name: 'capacite_max')]
    #[Assert\NotNull(message: 'La capacité est requise')]
    #[Assert\Range(min: 1, max: 100000, notInRangeMessage: 'La capacité doit être entre {{ min }} et {{ max }}')]
    private ?int $capaciteMax = null;

    #[ORM\Column(name: 'places_disponibles')]
    private ?int $placesDisponibles = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "L'organisateur est requis")]
    #[Assert\Length(max: 255, maxMessage: 'L\'organisateur ne peut pas dépasser {{ limit }} caractères')]
    private ?string $organisateur = null;

    #[ORM\Column(name: 'contact_email', length: 255, nullable: true)]
    #[Assert\Email(message: 'L\'adresse email de contact n\'est pas valide')]
    private ?string $contactEmail = null;

    #[ORM\Column(name: 'contact_tel', length: 50, nullable: true)]
    #[Assert\Regex(pattern: '/^\+?[\d\s\-()]{8,20}$/', message: 'Le numéro de téléphone n\'est pas valide')]
    private ?string $contactTel = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'actif';

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: 'date_modification', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateModification = null;

    /** @var Collection<int, Participation> */
    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'evenement')]
    private Collection $participations;

    /** @var Collection<int, Sponsor> */
    #[ORM\OneToMany(targetEntity: Sponsor::class, mappedBy: 'evenement')]
    private Collection $sponsors;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->sponsors       = new ArrayCollection();
    }

    // ── Lifecycle ──

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTime();
        $this->dateCreation    ??= $now;
        $this->dateModification  = $now;
        $this->placesDisponibles ??= $this->capaciteMax;
        $this->statut            ??= 'actif';
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->dateModification = new \DateTime();
    }

    // ── Helpers ──

    public function getTypeEnum(): ?TypeEvenement
    {
        return TypeEvenement::tryFrom($this->typeEvenement);
    }

    public function getStatutEnum(): ?StatutEvenement
    {
        return StatutEvenement::tryFrom($this->statut);
    }

    public function getGoogleMapsUrl(): ?string
    {
        $q = trim(($this->lieu ?? '') . ', ' . ($this->adresse ?? ''), ', ');
        return $q ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($q) : null;
    }

    public function isModifiable(): bool
    {
        return !in_array($this->statut, ['annule', 'termine'], true);
    }

    // ── Getters / Setters ──

    public function getIdEvenement(): ?int { return $this->idEvenement; }

    public function getTitre(): ?string { return $this->titre; }
<<<<<<< HEAD
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }
=======
    public function setTitre(?string $titre): static { $this->titre = $titre; return $this; }
>>>>>>> origin/main

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function getTypeEvenement(): ?string { return $this->typeEvenement; }
<<<<<<< HEAD
    public function setTypeEvenement(string $typeEvenement): static { $this->typeEvenement = $typeEvenement; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getHoraireDebut(): ?\DateTimeInterface { return $this->horaireDebut; }
    public function setHoraireDebut(\DateTimeInterface $horaireDebut): static { $this->horaireDebut = $horaireDebut; return $this; }

    public function getHoraireFin(): ?\DateTimeInterface { return $this->horaireFin; }
    public function setHoraireFin(\DateTimeInterface $horaireFin): static { $this->horaireFin = $horaireFin; return $this; }
=======
    public function setTypeEvenement(?string $typeEvenement): static { $this->typeEvenement = $typeEvenement; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getHoraireDebut(): ?\DateTimeInterface { return $this->horaireDebut; }
    public function setHoraireDebut(?\DateTimeInterface $horaireDebut): static { $this->horaireDebut = $horaireDebut; return $this; }

    public function getHoraireFin(): ?\DateTimeInterface { return $this->horaireFin; }
    public function setHoraireFin(?\DateTimeInterface $horaireFin): static { $this->horaireFin = $horaireFin; return $this; }
>>>>>>> origin/main

    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(?string $lieu): static { $this->lieu = $lieu; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }

    public function getCapaciteMax(): ?int { return $this->capaciteMax; }
<<<<<<< HEAD
    public function setCapaciteMax(int $capaciteMax): static { $this->capaciteMax = $capaciteMax; return $this; }
=======
    public function setCapaciteMax(?int $capaciteMax): static { $this->capaciteMax = $capaciteMax; return $this; }
>>>>>>> origin/main

    public function getPlacesDisponibles(): ?int { return $this->placesDisponibles; }
    public function setPlacesDisponibles(int $placesDisponibles): static { $this->placesDisponibles = $placesDisponibles; return $this; }

    public function getOrganisateur(): ?string { return $this->organisateur; }
    public function setOrganisateur(?string $organisateur): static { $this->organisateur = $organisateur; return $this; }

    public function getContactEmail(): ?string { return $this->contactEmail; }
    public function setContactEmail(?string $contactEmail): static { $this->contactEmail = $contactEmail; return $this; }

    public function getContactTel(): ?string { return $this->contactTel; }
    public function setContactTel(?string $contactTel): static { $this->contactTel = $contactTel; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $d): static { $this->dateCreation = $d; return $this; }

    public function getDateModification(): ?\DateTimeInterface { return $this->dateModification; }
    public function setDateModification(\DateTimeInterface $d): static { $this->dateModification = $d; return $this; }

    /** @return Collection<int, Participation> */
    public function getParticipations(): Collection { return $this->participations; }

    /** @return Collection<int, Sponsor> */
    public function getSponsors(): Collection { return $this->sponsors; }
}
