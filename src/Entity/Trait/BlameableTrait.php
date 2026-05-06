<?php

namespace App\Entity\Trait;

use App\Entity\User\Utilisateur;
use Doctrine\ORM\Mapping as ORM;

trait BlameableTrait
{
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Utilisateur $createdBy = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'updated_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Utilisateur $updatedBy = null;

    public function getCreatedBy(): ?Utilisateur
    {
        return $this->createdBy;
    }

    public function assignCreatedBy(Utilisateur $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?Utilisateur
    {
        return $this->updatedBy;
    }

    public function assignUpdatedBy(?Utilisateur $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}
