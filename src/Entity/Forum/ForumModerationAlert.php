<?php

namespace App\Entity\Forum;

use App\Entity\User\Utilisateur;
use App\Repository\Forum\ForumModerationAlertRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumModerationAlertRepository::class)]
#[ORM\Table(name: 'forum_moderation_alert')]
class ForumModerationAlert
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commentaire::class, inversedBy: 'moderationAlerts')]
    #[ORM\JoinColumn(name: 'commentaire_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Commentaire $commentaire = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(name: 'original_content', type: Types::TEXT)]
    private ?string $originalContent = null;

    #[ORM\Column(name: 'masked_content', type: Types::TEXT)]
    private ?string $maskedContent = null;

    #[ORM\Column(name: 'matched_words', type: Types::JSON)]
    private array $matchedWords = [];

    #[ORM\Column(length: 30)]
    private string $status = 'pending';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'reviewed_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $reviewedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommentaire(): ?Commentaire
    {
        return $this->commentaire;
    }

    public function setCommentaire(?Commentaire $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getOriginalContent(): ?string
    {
        return $this->originalContent;
    }

    public function setOriginalContent(string $originalContent): static
    {
        $this->originalContent = $originalContent;

        return $this;
    }

    public function getMaskedContent(): ?string
    {
        return $this->maskedContent;
    }

    public function setMaskedContent(string $maskedContent): static
    {
        $this->maskedContent = $maskedContent;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getMatchedWords(): array
    {
        return $this->matchedWords;
    }

    /**
     * @param list<string> $matchedWords
     */
    public function setMatchedWords(array $matchedWords): static
    {
        $this->matchedWords = array_values(array_map('strval', $matchedWords));

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getReviewedAt(): ?\DateTimeInterface
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeInterface $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
