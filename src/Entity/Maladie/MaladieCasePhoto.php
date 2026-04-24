<?php

namespace App\Entity\Maladie;

use App\Repository\Maladie\MaladieCasePhotoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaladieCasePhotoRepository::class)]
#[ORM\Table(name: 'maladie_case_photo')]
#[ORM\HasLifecycleCallbacks]
class MaladieCasePhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaladieCaseUpdate::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(name: 'case_update_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?MaladieCaseUpdate $caseUpdate = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCaseUpdate(): ?MaladieCaseUpdate
    {
        return $this->caseUpdate;
    }

    public function setCaseUpdate(?MaladieCaseUpdate $caseUpdate): self
    {
        $this->caseUpdate = $caseUpdate;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
