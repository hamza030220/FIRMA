<?php

namespace App\Entity\Forum;

use App\Entity\User\Utilisateur;
use App\Repository\Forum\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'post')]
class Post
{
    public const REACTION_LABELS = [
        'like' => ['label' => 'Like', 'emoji' => '👍'],
        'dislike' => ['label' => 'Dislike', 'emoji' => '👎'],
        'solidaire' => ['label' => 'Solidaire', 'emoji' => '🤝'],
        'encolere' => ['label' => 'En colère', 'emoji' => '😠'],
        'triste' => ['label' => 'Triste', 'emoji' => '😢'],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le titre ne peut pas dépasser 255 caractères.')]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu est obligatoire.')]
    #[Assert\Length(min: 10, minMessage: 'Le contenu doit contenir au moins 10 caractères.')]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'actif';

    #[ORM\Column(name: 'is_pinned', options: ['default' => false])]
    private bool $isPinned = false;

    /**
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Commentaire::class, orphanRemoval: true)]
    #[ORM\OrderBy(['dateCreation' => 'DESC'])]
    private Collection $commentaires;

    /**
     * @var Collection<int, ReactionPost>
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: ReactionPost::class, orphanRemoval: true)]
    private Collection $reactions;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->reactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;

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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned): static
    {
        $this->isPinned = $isPinned;

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    /**
     * @return Collection<int, ReactionPost>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    /**
     * @return array<string, int>
     */
    public function getReactionCounts(): array
    {
        $counts = array_fill_keys(array_keys(self::REACTION_LABELS), 0);

        foreach ($this->reactions as $reaction) {
            $type = $reaction->getType();
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
        }

        return $counts;
    }

    public function getReactionTotal(): int
    {
        return array_sum($this->getReactionCounts());
    }

    public function getUserReactionType(?Utilisateur $user): ?string
    {
        if ($user === null) {
            return null;
        }

        foreach ($this->reactions as $reaction) {
            if ($reaction->getUtilisateur()?->getId() === $user->getId()) {
                return $reaction->getType();
            }
        }

        return null;
    }

    /**
     * @return list<array{type: string, label: string, emoji: string, count: int, active: bool}>
     */
    public function getReactionItems(?Utilisateur $user = null): array
    {
        $counts = $this->getReactionCounts();
        $currentType = $this->getUserReactionType($user);
        $items = [];

        foreach (self::REACTION_LABELS as $type => $definition) {
            $items[] = [
                'type' => $type,
                'label' => $definition['label'],
                'emoji' => $definition['emoji'],
                'count' => $counts[$type] ?? 0,
                'active' => $currentType === $type,
            ];
        }

        return $items;
    }
}
