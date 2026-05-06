<?php

namespace App\Tests\Unit\Forum;

use App\Entity\Forum\ForumPostBookmark;
use App\Entity\Forum\Post;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class ForumPostBookmarkTest extends TestCase
{
    private ForumPostBookmark $bookmark;

    protected function setUp(): void
    {
        $this->bookmark = new ForumPostBookmark();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->bookmark->getId());
        $this->assertNull($this->bookmark->getPost());
        $this->assertNull($this->bookmark->getUtilisateur());
        $this->assertSame(ForumPostBookmark::TYPE_FAVORITE, $this->bookmark->getBookmarkType());
        $this->assertNull($this->bookmark->getCreatedAt());
    }

    public function testSetAndGetPost(): void
    {
        $post = new Post();
        $result = $this->bookmark->setPost($post);

        $this->assertSame($post, $this->bookmark->getPost());
        $this->assertSame($this->bookmark, $result);

        $this->bookmark->setPost(null);
        $this->assertNull($this->bookmark->getPost());
    }

    public function testSetAndGetUtilisateur(): void
    {
        $user = new Utilisateur();
        $result = $this->bookmark->setUtilisateur($user);

        $this->assertSame($user, $this->bookmark->getUtilisateur());
        $this->assertSame($this->bookmark, $result);

        $this->bookmark->setUtilisateur(null);
        $this->assertNull($this->bookmark->getUtilisateur());
    }

    public function testSetAndGetBookmarkType(): void
    {
        $result = $this->bookmark->setBookmarkType(ForumPostBookmark::TYPE_SAVED);

        $this->assertSame(ForumPostBookmark::TYPE_SAVED, $this->bookmark->getBookmarkType());
        $this->assertSame($this->bookmark, $result);
    }

    public function testSetBookmarkTypeNormalizesValue(): void
    {
        $this->bookmark->setBookmarkType(' SAVED ');
        $this->assertSame(ForumPostBookmark::TYPE_SAVED, $this->bookmark->getBookmarkType());
    }

    public function testSetBookmarkTypeFallsBackToFavoriteForInvalidValue(): void
    {
        $this->bookmark->setBookmarkType('archived');

        $this->assertSame(ForumPostBookmark::TYPE_FAVORITE, $this->bookmark->getBookmarkType());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $date = new \DateTime('2026-04-23 08:00:00');
        $this->bookmark->initializeTimestamp($date);

        $this->assertSame($date, $this->bookmark->getCreatedAt());
    }

    public function testBookmarkCanBeConfiguredEndToEnd(): void
    {
        $post = new Post();
        $user = new Utilisateur();
        $date = new \DateTime('2026-04-23 08:00:00');

        $this->bookmark
            ->setPost($post)
            ->setUtilisateur($user)
            ->setBookmarkType(' saved ')
            ->initializeTimestamp($date);

        $this->assertSame($post, $this->bookmark->getPost());
        $this->assertSame($user, $this->bookmark->getUtilisateur());
        $this->assertSame(ForumPostBookmark::TYPE_SAVED, $this->bookmark->getBookmarkType());
        $this->assertSame($date, $this->bookmark->getCreatedAt());
    }

    public function testSettersForAuditAreAvailableViaInitializer(): void
    {
        $date = new \DateTime('2026-04-23 08:00:00');
        $this->bookmark->initializeTimestamp($date);

        $this->assertSame($date, $this->bookmark->getCreatedAt());
    }

    public function testBookmarkTypeCanBeResetToDefaultOnInvalidValue(): void
    {
        $this->bookmark->setBookmarkType(ForumPostBookmark::TYPE_SAVED);
        $this->assertSame(ForumPostBookmark::TYPE_SAVED, $this->bookmark->getBookmarkType());

        $this->bookmark->setBookmarkType('invalid-value');
        $this->assertSame(ForumPostBookmark::TYPE_FAVORITE, $this->bookmark->getBookmarkType());
    }
}
