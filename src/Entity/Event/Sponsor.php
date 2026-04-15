<?php

namespace App\Entity\Event;

use App\Repository\Event\SponsorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SponsorRepository::class)]
#[ORM\Table(name: 'sponsors')]
class Sponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_sponsor')]
    private ?int $idSponsor = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(min: 2, max: 200, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $nom = null;

    #[ORM\Column(name: 'logo_url', length: 255, nullable: true)]
    private ?string $logoUrl = null;

    #[ORM\Column(name: 'site_web', length: 255, nullable: true)]
    #[Assert\Url(message: 'L\'URL du site web n\'est pas valide')]
    #[Assert\Length(max: 255, maxMessage: 'L\'URL ne peut pas dépasser {{ limit }} caractères')]
    private ?string $siteWeb = null;

    #[ORM\Column(name: 'email_contact', length: 150, nullable: true)]
    #[Assert\NotBlank(message: 'L\'email de contact est requis')]
    #[Assert\Email(message: 'L\'adresse email n\'est pas valide')]
    #[Assert\Length(max: 150, maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères')]
    private ?string $emailContact = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(message: 'Le téléphone est requis')]
    #[Assert\Regex(pattern: '/^\+?[\d\s\-()]{8,20}$/', message: 'Le numéro de téléphone n\'est pas valide (8 à 20 caractères)')]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'montant_contribution', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Le montant ne peut pas être négatif')]
    private ?string $montantContribution = '0';

    #[ORM\Column(name: 'secteur_activite', length: 20)]
    private ?string $secteurActivite = 'autre';

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'sponsors')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id_evenement', nullable: true, onDelete: 'SET NULL')]
    private ?Evenement $evenement = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    // ── Helpers ──

    public function getSecteurEnum(): ?SecteurActivite
    {
        return SecteurActivite::tryFrom($this->secteurActivite);
    }

    public function isCatalog(): bool
    {
        return $this->evenement === null;
    }

    /**
     * Clone ce sponsor pour l'assigner à un événement.
     */
    public function cloneForEvent(Evenement $evenement, ?string $montant = null): self
    {
        $clone = new self();
        $clone->nom                 = $this->nom;
        $clone->logoUrl             = $this->logoUrl;
        $clone->siteWeb             = $this->siteWeb;
        $clone->emailContact        = $this->emailContact;
        $clone->telephone           = $this->telephone;
        $clone->description         = $this->description;
        $clone->secteurActivite     = $this->secteurActivite;
        $clone->montantContribution = $montant ?? $this->montantContribution;
        $clone->evenement           = $evenement;
        $clone->dateCreation        = new \DateTime();

        return $clone;
    }

    // ── Getters / Setters ──

    public function getIdSponsor(): ?int { return $this->idSponsor; }

    public function getNom(): ?string { return $this->nom; }
<<<<<<< HEAD
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
=======
    public function setNom(?string $nom): static { $this->nom = $nom; return $this; }
>>>>>>> origin/main

    public function getLogoUrl(): ?string { return $this->logoUrl; }
    public function setLogoUrl(?string $logoUrl): static { $this->logoUrl = $logoUrl; return $this; }

    public function getSiteWeb(): ?string { return $this->siteWeb; }
    public function setSiteWeb(?string $siteWeb): static { $this->siteWeb = $siteWeb; return $this; }

    public function getEmailContact(): ?string { return $this->emailContact; }
    public function setEmailContact(?string $email): static { $this->emailContact = $email; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getMontantContribution(): ?string { return $this->montantContribution; }
    public function setMontantContribution(?string $m): static { $this->montantContribution = $m ?? '0'; return $this; }

    public function getSecteurActivite(): ?string { return $this->secteurActivite; }
<<<<<<< HEAD
    public function setSecteurActivite(string $s): static { $this->secteurActivite = $s; return $this; }
=======
    public function setSecteurActivite(?string $s): static { $this->secteurActivite = $s; return $this; }
>>>>>>> origin/main

    public function getEvenement(): ?Evenement { return $this->evenement; }
    public function setEvenement(?Evenement $e): static { $this->evenement = $e; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $d): static { $this->dateCreation = $d; return $this; }
}
