<?php

namespace App\Entity\User;

use App\Repository\User\UtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateurs')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    private ?string $prenom = null;

    #[ORM\Column(length: 150, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'adresse email n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 20, name: 'type_user')]
    private ?string $typeUser = 'client';

    #[ORM\Column(length: 255, name: 'mot_de_passe')]
    private ?string $motDePasse = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    // ── Ban fields ──
 
    #[ORM\Column(name: 'is_banned', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isBanned = false;
 
    #[ORM\Column(name: 'ban_reason', length: 255, nullable: true)]
    private ?string $banReason = null;
 
    #[ORM\Column(name: 'banned_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $bannedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;
        return $this;
    }

    public function getTypeUser(): ?string
    {
        return $this->typeUser;
    }

    public function setTypeUser(string $typeUser): static
    {
        $this->typeUser = $typeUser;
        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->motDePasse;
    }

    public function setMotDePasse(string $motDePasse): static
    {
        $this->motDePasse = $motDePasse;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    // ── Ban getters/setters ──
 
    public function isBanned(): bool { return $this->isBanned; }
    public function setIsBanned(bool $isBanned): static { $this->isBanned = $isBanned; return $this; }
 
    public function getBanReason(): ?string { return $this->banReason; }
    public function setBanReason(?string $banReason): static { $this->banReason = $banReason; return $this; }
 
    public function getBannedAt(): ?\DateTimeInterface { return $this->bannedAt; }
    public function setBannedAt(?\DateTimeInterface $bannedAt): static { $this->bannedAt = $bannedAt; return $this; }
 

    // ── UserInterface ──

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return match ($this->typeUser) {
            'admin' => ['ROLE_ADMIN', 'ROLE_USER'],
            'technicien' => ['ROLE_TECHNICIEN', 'ROLE_USER'],
            default => ['ROLE_USER'],
        };
    }

    public function getPassword(): ?string
    {
        return $this->motDePasse;
    }

    public function eraseCredentials(): void
    {
    }

    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }
}
