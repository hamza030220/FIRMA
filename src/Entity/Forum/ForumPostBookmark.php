<?php

namespace App\Entity\Forum;

use App\Entity\User\Utilisateur;
use App\Repository\Forum\ForumPostBookmarkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumPostBookmarkRepository::class)]
#[ORM\Table(name: 'forum_post_bookmark')]
#[ORM\UniqueConstraint(name: 'uniq_forum_post_bookmark_user_post_type', columns: ['post_id', 'utilisateur_id', 'bookmark_type'])]
#[ORM\Index(name: 'idx_forum_post_bookmark_user', columns: ['utilisateur_id'])]
#[ORM\Index(name: 'idx_forum_post_bookmark_post', columns: ['post_id'])]
#[ORM\Index(name: 'idx_forum_post_bookmark_type', columns: ['bookmark_type'])]
class ForumPostBookmark
{
    public const TYPE_FAVORITE = 'favorite';
    public const TYPE_SAVED = 'saved';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(name: 'bookmark_type', length: 20)]
    private string $bookmarkType = self::TYPE_FAVORITE;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

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

    public function getBookmarkType(): string
    {
        return $this->bookmarkType;
    }

    public function setBookmarkType(string $bookmarkType): static
    {
        $bookmarkType = trim(mb_strtolower($bookmarkType));
        $this->bookmarkType = in_array($bookmarkType, [self::TYPE_FAVORITE, self::TYPE_SAVED], true)
            ? $bookmarkType
            : self::TYPE_FAVORITE;

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
}
