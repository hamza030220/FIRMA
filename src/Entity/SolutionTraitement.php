<?php
// src/Entity/SolutionTraitement.php

namespace App\Entity;

use App\Repository\SolutionTraitementRepository;  // ← LIGNE AJOUTÉE
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SolutionTraitementRepository::class)]
#[ORM\Table(name: "solution_traitement")]
#[ORM\HasLifecycleCallbacks]
class SolutionTraitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id")]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'solutionTraitements')]
    #[ORM\JoinColumn(name: "maladie_id", referencedColumnName: "id_maladie", nullable: false)]
    private ?Maladie $maladie = null;

    #[ORM\Column(name: "titre", length: 200)]
    private ?string $titre = null;

    #[ORM\Column(name: "solution", type: 'text')]
    private ?string $solution = null;

    #[ORM\Column(name: "etapes", type: 'text', nullable: true)]
    private ?string $etapes = null;

    #[ORM\Column(name: "produits_recommandes", type: 'text', nullable: true)]
    private ?string $produitsRecommandes = null;

    #[ORM\Column(name: "conseils_prevention", type: 'text', nullable: true)]
    private ?string $conseilsPrevention = null;

    #[ORM\Column(name: "duree_traitement", length: 100, nullable: true)]
    private ?string $dureeTraitement = null;

    #[ORM\Column(name: "usage_count")]
    private ?int $usageCount = 0;

    #[ORM\Column(name: "feedback_positive")]
    private ?int $feedbackPositive = 0;

    #[ORM\Column(name: "feedback_negative")]
    private ?int $feedbackNegative = 0;

    #[ORM\Column(name: "last_used_at", nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(name: "last_user_id", nullable: true)]
    private ?int $lastUserId = null;

    #[ORM\Column(name: "created_by", nullable: true)]
    private ?int $createdBy = null;

    #[ORM\Column(name: "created_at")]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: "updated_at", nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getSuccessRate(): ?float
    {
        if ($this->usageCount > 0) {
            return round(($this->feedbackPositive * 100) / $this->usageCount, 2);
        }
        return null;
    }

    public function incrementUsageCount(): self
    {
        $this->usageCount++;
        $this->lastUsedAt = new \DateTimeImmutable();
        return $this;
    }

    // ============ GETTERS & SETTERS ============
    
    public function getId(): ?int { return $this->id; }
    public function getMaladie(): ?Maladie { return $this->maladie; }
    public function setMaladie(?Maladie $maladie): self { $this->maladie = $maladie; return $this; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): self { $this->titre = $titre; return $this; }
    public function getSolution(): ?string { return $this->solution; }
    public function setSolution(string $solution): self { $this->solution = $solution; return $this; }
    public function getEtapes(): ?string { return $this->etapes; }
    public function setEtapes(?string $etapes): self { $this->etapes = $etapes; return $this; }
    public function getProduitsRecommandes(): ?string { return $this->produitsRecommandes; }
    public function setProduitsRecommandes(?string $produitsRecommandes): self { $this->produitsRecommandes = $produitsRecommandes; return $this; }
    public function getConseilsPrevention(): ?string { return $this->conseilsPrevention; }
    public function setConseilsPrevention(?string $conseilsPrevention): self { $this->conseilsPrevention = $conseilsPrevention; return $this; }
    public function getDureeTraitement(): ?string { return $this->dureeTraitement; }
    public function setDureeTraitement(?string $dureeTraitement): self { $this->dureeTraitement = $dureeTraitement; return $this; }
    public function getUsageCount(): ?int { return $this->usageCount; }
    public function setUsageCount(int $usageCount): self { $this->usageCount = $usageCount; return $this; }
    public function getFeedbackPositive(): ?int { return $this->feedbackPositive; }
    public function setFeedbackPositive(int $feedbackPositive): self { $this->feedbackPositive = $feedbackPositive; return $this; }
    public function getFeedbackNegative(): ?int { return $this->feedbackNegative; }
    public function setFeedbackNegative(int $feedbackNegative): self { $this->feedbackNegative = $feedbackNegative; return $this; }
    public function getLastUsedAt(): ?\DateTimeImmutable { return $this->lastUsedAt; }
    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): self { $this->lastUsedAt = $lastUsedAt; return $this; }
    public function getLastUserId(): ?int { return $this->lastUserId; }
    public function setLastUserId(?int $lastUserId): self { $this->lastUserId = $lastUserId; return $this; }
    public function getCreatedBy(): ?int { return $this->createdBy; }
    public function setCreatedBy(?int $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
}