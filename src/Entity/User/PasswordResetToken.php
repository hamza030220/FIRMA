<?php

namespace App\Entity\User;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'password_reset_tokens')]
class PasswordResetToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $utilisateur;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $expiresAt;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $used = false;

    public function __construct(Utilisateur $utilisateur, string $token, \DateTimeInterface $expiresAt)
    {
        $this->utilisateur = $utilisateur;
        $this->token       = $token;
        $this->expiresAt   = $expiresAt;
    }

    public function getId(): ?int { return $this->id; }
    public function getUtilisateur(): Utilisateur { return $this->utilisateur; }
    public function getToken(): string { return $this->token; }
    public function getExpiresAt(): \DateTimeInterface { return $this->expiresAt; }
    public function isUsed(): bool { return $this->used; }
    public function markUsed(): void { $this->used = true; }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }
}