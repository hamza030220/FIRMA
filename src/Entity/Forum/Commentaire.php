<?php

namespace App\Entity\Forum;

use App\Entity\Forum\Traits\ForumTextRepairTrait;
use App\Entity\User\Utilisateur;
use App\Repository\Forum\CommentaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\Table(name: 'commentaire')]
class Commentaire
{
    use ForumTextRepairTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Post $post;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $utilisateur;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le commentaire est obligatoire.')]
    #[Assert\Length(min: 2, minMessage: 'Le commentaire doit contenir au moins 2 caractères.', max: 1000, maxMessage: 'Le commentaire ne peut pas dépasser 1000 caractères.')]
    private string $contenu;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dateCreation;

    #[ORM\Column(name: 'image_path', length: 255, nullable: true)]
    private ?string $imagePath = null;

    /**
     * @var Collection<int, ForumModerationAlert>
     */
    #[ORM\OneToMany(mappedBy: 'commentaire', targetEntity: ForumModerationAlert::class)]
    private Collection $moderationAlerts;

    public function __construct()
    {
        $this->moderationAlerts = new ArrayCollection();
    }

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

    public function getContenu(): ?string
    {
        return isset($this->contenu) ? $this->repairForumText($this->contenu) : null;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

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

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /**
     * @return Collection<int, ForumModerationAlert>
     */
    public function getModerationAlerts(): Collection
    {
        return $this->moderationAlerts;
    }
}
