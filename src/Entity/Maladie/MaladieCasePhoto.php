<?php

namespace App\Entity\Maladie;

use App\Entity\Trait\BlameableTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\Maladie\MaladieCasePhotoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaladieCasePhotoRepository::class)]
#[ORM\Table(name: 'maladie_case_photo')]
#[ORM\HasLifecycleCallbacks]
class MaladieCasePhoto
{
    use BlameableTrait;
    use TimestampableTrait { setCreatedAt as protected traitSetCreatedAt; }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MaladieCaseUpdate::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(name: 'case_update_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?MaladieCaseUpdate $caseUpdate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    public function __construct()
    {
        $this->traitSetCreatedAt(new \DateTimeImmutable());
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function initializeTimestamp(): static
    {
        $this->traitSetCreatedAt(new \DateTimeImmutable());

        return $this;
    }
}
