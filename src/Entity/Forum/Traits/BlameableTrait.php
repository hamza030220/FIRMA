<?php

namespace App\Entity\Forum\Traits;

use Doctrine\ORM\Mapping as ORM;

trait BlameableTrait
{
    #[ORM\Column(name: 'created_by', nullable: true)]
    protected ?int $createdBy = null;

    #[ORM\Column(name: 'updated_by', nullable: true)]
    protected ?int $updatedBy = null;

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?int $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }
}
