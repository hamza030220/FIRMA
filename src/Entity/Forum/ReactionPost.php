<?php

namespace App\Entity\Forum;

use App\Entity\Trait\BlameableTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Entity\User\Utilisateur;
use App\Repository\Forum\ReactionPostRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReactionPostRepository::class)]
#[ORM\Table(name: 'post_reaction')]
#[ORM\UniqueConstraint(name: 'uniq_post_reaction_user_post', columns: ['post_id', 'utilisateur_id'])]
#[ORM\Index(name: 'idx_post_reaction_post', columns: ['post_id'])]
#[ORM\Index(name: 'idx_post_reaction_user', columns: ['utilisateur_id'])]
class ReactionPost
{
    use BlameableTrait;
    use TimestampableTrait { setCreatedAt as protected traitSetCreatedAt; }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'reactions')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Post $post;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $utilisateur;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'La reaction est obligatoire.')]
    private string $type;

    #[ORM\Column(name: 'date_creation', type: 'datetime')]
    private \DateTimeInterface $dateCreation;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPost(): ?Post
    {
        return isset($this->post) ? $this->post : null;
    }

    public function setPost(?Post $post): static
    {
        if ($post === null) {
            unset($this->post);
            return $this;
        }

        $this->post = $post;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return isset($this->utilisateur) ? $this->utilisateur : null;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        if ($utilisateur === null) {
            unset($this->utilisateur);
            return $this;
        }

        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getType(): ?string
    {
        return isset($this->type) ? $this->type : null;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return isset($this->dateCreation) ? $this->dateCreation : null;
    }

    public function initializeDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    #[ORM\PrePersist]
    public function setDateCreationValue(): void
    {
        $this->dateCreation ??= new \DateTimeImmutable();
        $this->traitSetCreatedAt(new \DateTimeImmutable());
    }
}
