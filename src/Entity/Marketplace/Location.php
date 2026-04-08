<?php

namespace App\Entity\Marketplace;

use App\Entity\User\Utilisateur;
use App\Repository\Marketplace\LocationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\Table(name: 'locations')]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(length: 20, name: 'type_location')]
    private ?string $typeLocation = null;

    #[ORM\ManyToOne(targetEntity: Vehicule::class)]
    #[ORM\JoinColumn(name: 'vehicule_id', nullable: true)]
    private ?Vehicule $vehicule = null;

    #[ORM\ManyToOne(targetEntity: Terrain::class)]
    #[ORM\JoinColumn(name: 'terrain_id', nullable: true)]
    private ?Terrain $terrain = null;

    #[ORM\Column(length: 50, name: 'numero_location')]
    private ?string $numeroLocation = null;

    #[ORM\Column(name: 'date_debut', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: 'duree_jours', nullable: true)]
    private ?int $dureeJours = null;

    #[ORM\Column(name: 'prix_total', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixTotal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $caution = '0.00';

    #[ORM\Column(length: 20)]
    private ?string $statut = 'en_attente';

    #[ORM\Column(name: 'date_reservation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->dateReservation = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(?Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }

    public function getTypeLocation(): ?string { return $this->typeLocation; }
    public function setTypeLocation(string $typeLocation): static { $this->typeLocation = $typeLocation; return $this; }

    public function getVehicule(): ?Vehicule { return $this->vehicule; }
    public function setVehicule(?Vehicule $vehicule): static { $this->vehicule = $vehicule; return $this; }

    public function getTerrain(): ?Terrain { return $this->terrain; }
    public function setTerrain(?Terrain $terrain): static { $this->terrain = $terrain; return $this; }

    public function getNumeroLocation(): ?string { return $this->numeroLocation; }
    public function setNumeroLocation(string $numeroLocation): static { $this->numeroLocation = $numeroLocation; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getDureeJours(): ?int { return $this->dureeJours; }
    public function setDureeJours(?int $dureeJours): static { $this->dureeJours = $dureeJours; return $this; }

    public function getPrixTotal(): ?string { return $this->prixTotal; }
    public function setPrixTotal(string $prixTotal): static { $this->prixTotal = $prixTotal; return $this; }

    public function getCaution(): ?string { return $this->caution; }
    public function setCaution(?string $caution): static { $this->caution = $caution; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getDateReservation(): ?\DateTimeInterface { return $this->dateReservation; }
    public function setDateReservation(\DateTimeInterface $dateReservation): static { $this->dateReservation = $dateReservation; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getItemName(): string
    {
        if ($this->typeLocation === 'vehicule' && $this->vehicule) {
            return $this->vehicule->getNom() ?? '';
        }
        if ($this->typeLocation === 'terrain' && $this->terrain) {
            return $this->terrain->getTitre() ?? '';
        }
        return '';
    }
}
