<?php

namespace App\Tests\Unit\Forum;

use App\Entity\Forum\Post;
use App\Entity\Forum\ReactionPost;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class ReactionPostTest extends TestCase
{
    private ReactionPost $reaction;

    protected function setUp(): void
    {
        $this->reaction = new ReactionPost();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->reaction->getId());
        $this->assertNull($this->reaction->getPost());
        $this->assertNull($this->reaction->getUtilisateur());
        $this->assertNull($this->reaction->getType());
        $this->assertNull($this->reaction->getDateCreation());
    }

    public function testSetAndGetPost(): void
    {
        $post = new Post();
        $result = $this->reaction->setPost($post);

        $this->assertSame($post, $this->reaction->getPost());
        $this->assertSame($this->reaction, $result);

        $this->reaction->setPost(null);
        $this->assertNull($this->reaction->getPost());
    }

    public function testSetAndGetUtilisateur(): void
    {
        $user = new Utilisateur();
        $result = $this->reaction->setUtilisateur($user);

        $this->assertSame($user, $this->reaction->getUtilisateur());
        $this->assertSame($this->reaction, $result);

        $this->reaction->setUtilisateur(null);
        $this->assertNull($this->reaction->getUtilisateur());
    }

    public function testSetAndGetType(): void
    {
        foreach (['like', 'dislike', 'solidaire', 'encolere', 'triste'] as $type) {
            $this->reaction->setType($type);
            $this->assertSame($type, $this->reaction->getType());
        }
    }

    public function testSetAndGetDateCreation(): void
    {
        $date = new \DateTime('2026-04-22 09:15:00');
        $this->reaction->initializeDateCreation($date);

        $this->assertSame($date, $this->reaction->getDateCreation());
    }

    public function testReactionCanBeConfiguredEndToEnd(): void
    {
        $post = new Post();
        $user = new Utilisateur();
        $date = new \DateTime('2026-04-22 09:15:00');

        $this->reaction
            ->setPost($post)
            ->setUtilisateur($user)
            ->setType('solidaire')
            ->initializeDateCreation($date);

        $this->assertSame($post, $this->reaction->getPost());
        $this->assertSame($user, $this->reaction->getUtilisateur());
        $this->assertSame('solidaire', $this->reaction->getType());
        $this->assertSame($date, $this->reaction->getDateCreation());

        $this->reaction->setPost(null);
        $this->reaction->setUtilisateur(null);

        $this->assertNull($this->reaction->getPost());
        $this->assertNull($this->reaction->getUtilisateur());
    }
}
