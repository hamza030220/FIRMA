<?php

namespace App\Entity\Marketplace;

use App\Entity\User\Utilisateur;
use App\Repository\Marketplace\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commandes')]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(length: 50, name: 'numero_commande')]
    private ?string $numeroCommande = null;

    #[ORM\Column(name: 'montant_total', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantTotal = null;

    #[ORM\Column(length: 20, name: 'statut_paiement')]
    private ?string $statutPaiement = 'en_attente';

    #[ORM\Column(length: 30, name: 'statut_livraison')]
    private ?string $statutLivraison = 'en_attente';

    #[ORM\Column(name: 'adresse_livraison', type: Types::TEXT)]
    private ?string $adresseLivraison = null;

    #[ORM\Column(length: 100, nullable: true, name: 'ville_livraison')]
    private ?string $villeLivraison = null;

    #[ORM\Column(name: 'date_commande', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCommande = null;

    #[ORM\Column(name: 'date_livraison', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateLivraison = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /** @var Collection<int, DetailCommande> */
    #[ORM\OneToMany(targetEntity: DetailCommande::class, mappedBy: 'commande', cascade: ['remove'])]
    private Collection $details;

    public function __construct()
    {
        $this->details = new ArrayCollection();
        $this->dateCommande = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(?Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }

    public function getNumeroCommande(): ?string { return $this->numeroCommande; }
    public function setNumeroCommande(?string $numeroCommande): static { $this->numeroCommande = $numeroCommande; return $this; }

    public function getMontantTotal(): ?string { return $this->montantTotal; }
    public function setMontantTotal(string $montantTotal): static { $this->montantTotal = $montantTotal; return $this; }

    public function getStatutPaiement(): ?string { return $this->statutPaiement; }
    public function setStatutPaiement(string $statutPaiement): static { $this->statutPaiement = $statutPaiement; return $this; }

    public function getStatutLivraison(): ?string { return $this->statutLivraison; }
    public function setStatutLivraison(string $statutLivraison): static { $this->statutLivraison = $statutLivraison; return $this; }

    public function getAdresseLivraison(): ?string { return $this->adresseLivraison; }
    public function setAdresseLivraison(string $adresseLivraison): static { $this->adresseLivraison = $adresseLivraison; return $this; }

    public function getVilleLivraison(): ?string { return $this->villeLivraison; }
    public function setVilleLivraison(?string $villeLivraison): static { $this->villeLivraison = $villeLivraison; return $this; }

    public function getDateCommande(): ?\DateTimeInterface { return $this->dateCommande; }
    public function setDateCommande(\DateTimeInterface $dateCommande): static { $this->dateCommande = $dateCommande; return $this; }

    public function getDateLivraison(): ?\DateTimeInterface { return $this->dateLivraison; }
    public function setDateLivraison(?\DateTimeInterface $dateLivraison): static { $this->dateLivraison = $dateLivraison; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    /** @return Collection<int, DetailCommande> */
    public function getDetails(): Collection { return $this->details; }
}
