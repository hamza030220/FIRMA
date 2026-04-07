<?php

namespace App\Entity\Marketplace;

use App\Repository\Marketplace\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
#[ORM\Table(name: 'categories')]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 20, name: 'type_produit')]
    private ?string $typeProduit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, Equipement> */
    #[ORM\OneToMany(targetEntity: Equipement::class, mappedBy: 'categorie')]
    private Collection $equipements;

    /** @var Collection<int, Vehicule> */
    #[ORM\OneToMany(targetEntity: Vehicule::class, mappedBy: 'categorie')]
    private Collection $vehicules;

    /** @var Collection<int, Terrain> */
    #[ORM\OneToMany(targetEntity: Terrain::class, mappedBy: 'categorie')]
    private Collection $terrains;

    public function __construct()
    {
        $this->equipements = new ArrayCollection();
        $this->vehicules = new ArrayCollection();
        $this->terrains = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getTypeProduit(): ?string { return $this->typeProduit; }
    public function setTypeProduit(string $typeProduit): static { $this->typeProduit = $typeProduit; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    /** @return Collection<int, Equipement> */
    public function getEquipements(): Collection { return $this->equipements; }

    /** @return Collection<int, Vehicule> */
    public function getVehicules(): Collection { return $this->vehicules; }

    /** @return Collection<int, Terrain> */
    public function getTerrains(): Collection { return $this->terrains; }

    public function __toString(): string { return $this->nom ?? ''; }
}
