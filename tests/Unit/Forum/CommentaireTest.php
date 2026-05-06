<?php

namespace App\Tests\Unit\Forum;

use App\Entity\Forum\Commentaire;
use App\Entity\Forum\ForumModerationAlert;
use App\Entity\Forum\Post;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class CommentaireTest extends TestCase
{
    private Commentaire $commentaire;

    protected function setUp(): void
    {
        $this->commentaire = new Commentaire();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->commentaire->getId());
        $this->assertNull($this->commentaire->getPost());
        $this->assertNull($this->commentaire->getUtilisateur());
        $this->assertNull($this->commentaire->getContenu());
        $this->assertNull($this->commentaire->getDateCreation());
        $this->assertNull($this->commentaire->getImagePath());
        $this->assertCount(0, $this->commentaire->getModerationAlerts());
    }

    public function testSetAndGetPost(): void
    {
        $post = new Post();
        $result = $this->commentaire->setPost($post);

        $this->assertSame($post, $this->commentaire->getPost());
        $this->assertSame($this->commentaire, $result);

        $this->commentaire->setPost(null);
        $this->assertNull($this->commentaire->getPost());
    }

    public function testSetAndGetUtilisateur(): void
    {
        $user = new Utilisateur();
        $result = $this->commentaire->setUtilisateur($user);

        $this->assertSame($user, $this->commentaire->getUtilisateur());
        $this->assertSame($this->commentaire, $result);

        $this->commentaire->setUtilisateur(null);
        $this->assertNull($this->commentaire->getUtilisateur());
    }

    public function testSetAndGetContenu(): void
    {
        $this->commentaire->setContenu('Merci pour le partage.');

        $this->assertSame('Merci pour le partage.', $this->commentaire->getContenu());
    }

    public function testSetAndGetDateCreation(): void
    {
        $date = new \DateTime('2026-04-21 14:00:00');
        $this->commentaire->initializeDateCreation($date);

        $this->assertSame($date, $this->commentaire->getDateCreation());
    }

    public function testSetAndGetImagePath(): void
    {
        $this->commentaire->setImagePath('uploads/commentaires/photo.png');
        $this->assertSame('uploads/commentaires/photo.png', $this->commentaire->getImagePath());

        $this->commentaire->setImagePath(null);
        $this->assertNull($this->commentaire->getImagePath());
    }

    public function testModerationAlertsCollection(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->commentaire->getModerationAlerts());

        $this->commentaire->getModerationAlerts()->add(new ForumModerationAlert());
        $this->assertCount(1, $this->commentaire->getModerationAlerts());
    }

    public function testCommentaireCanBeConfiguredEndToEnd(): void
    {
        $post = new Post();
        $user = new Utilisateur();
        $date = new \DateTime('2026-04-21 14:00:00');

        $this->commentaire
            ->setPost($post)
            ->setUtilisateur($user)
            ->setContenu('Merci pour le partage.')
            ->initializeDateCreation($date)
            ->setImagePath('uploads/commentaires/photo.png');

        $this->assertSame($post, $this->commentaire->getPost());
        $this->assertSame($user, $this->commentaire->getUtilisateur());
        $this->assertSame('Merci pour le partage.', $this->commentaire->getContenu());
        $this->assertSame($date, $this->commentaire->getDateCreation());
        $this->assertSame('uploads/commentaires/photo.png', $this->commentaire->getImagePath());
        $this->assertCount(0, $this->commentaire->getModerationAlerts());
    }
}
