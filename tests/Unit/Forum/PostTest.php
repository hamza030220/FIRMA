<?php

namespace App\Tests\Unit\Forum;

use App\Entity\Forum\Commentaire;
use App\Entity\Forum\Post;
use App\Entity\Forum\ReactionPost;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    private Post $post;

    protected function setUp(): void
    {
        $this->post = new Post();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->post->getId());
        $this->assertNull($this->post->getUtilisateur());
        $this->assertNull($this->post->getTitre());
        $this->assertNull($this->post->getContenu());
        $this->assertNull($this->post->getCategorie());
        $this->assertNull($this->post->getDateCreation());
        $this->assertSame('actif', $this->post->getStatut());
        $this->assertFalse($this->post->isPinned());
        $this->assertCount(0, $this->post->getCommentaires());
        $this->assertCount(0, $this->post->getReactions());
    }

    public function testSetAndGetUtilisateur(): void
    {
        $user = new Utilisateur();
        $result = $this->post->setUtilisateur($user);

        $this->assertSame($user, $this->post->getUtilisateur());
        $this->assertSame($this->post, $result);
    }

    public function testSetAndGetTitre(): void
    {
        $result = $this->post->setTitre('Probleme irrigation');

        $this->assertSame('Probleme irrigation', $this->post->getTitre());
        $this->assertSame($this->post, $result);
    }

    public function testSetAndGetContenu(): void
    {
        $this->post->setContenu('Comment ameliorer le rendement de mes tomates ?');

        $this->assertSame('Comment ameliorer le rendement de mes tomates ?', $this->post->getContenu());
    }

    public function testSetAndGetCategorie(): void
    {
        $this->post->setCategorie('Irrigation');
        $this->assertSame('Irrigation', $this->post->getCategorie());

        $this->post->setCategorie(null);
        $this->assertNull($this->post->getCategorie());
    }

    public function testSetAndGetDateCreation(): void
    {
        $date = new \DateTime('2026-04-20 10:30:00');
        $this->post->initializeDateCreation($date);

        $this->assertSame($date, $this->post->getDateCreation());
    }

    public function testSetAndGetStatut(): void
    {
        foreach (['actif', 'archive', 'masque'] as $statut) {
            $this->post->setStatut($statut);
            $this->assertSame($statut, $this->post->getStatut());
        }
    }

    public function testSetAndGetIsPinned(): void
    {
        $result = $this->post->setIsPinned(true);

        $this->assertTrue($this->post->isPinned());
        $this->assertSame($this->post, $result);

        $this->post->setIsPinned(false);
        $this->assertFalse($this->post->isPinned());
    }

    public function testCollectionsAreInitialized(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->post->getCommentaires());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->post->getReactions());
    }

    public function testCollectionsAcceptItems(): void
    {
        $this->post->getCommentaires()->add(new Commentaire());
        $this->post->getReactions()->add(new ReactionPost());

        $this->assertCount(1, $this->post->getCommentaires());
        $this->assertCount(1, $this->post->getReactions());
    }

    public function testReactionCountsAreInitializedToZero(): void
    {
        $this->assertSame([
            'like' => 0,
            'dislike' => 0,
            'solidaire' => 0,
            'encolere' => 0,
            'triste' => 0,
        ], $this->post->getReactionCounts());
        $this->assertSame(0, $this->post->getReactionTotal());
    }

    public function testReactionCountsIgnoreUnknownTypes(): void
    {
        $this->post->getReactions()->add((new ReactionPost())->setType('like'));
        $this->post->getReactions()->add((new ReactionPost())->setType('like'));
        $this->post->getReactions()->add((new ReactionPost())->setType('triste'));
        $this->post->getReactions()->add((new ReactionPost())->setType('inconnu'));

        $this->assertSame([
            'like' => 2,
            'dislike' => 0,
            'solidaire' => 0,
            'encolere' => 0,
            'triste' => 1,
        ], $this->post->getReactionCounts());
        $this->assertSame(3, $this->post->getReactionTotal());
    }

    public function testGetUserReactionTypeReturnsNullWithoutUser(): void
    {
        $this->assertNull($this->post->getUserReactionType(null));
    }

    public function testGetUserReactionTypeReturnsMatchingReaction(): void
    {
        $currentUser = $this->createUserWithId(7);
        $otherUser = $this->createUserWithId(8);

        $this->post->getReactions()->add((new ReactionPost())->setUtilisateur($otherUser)->setType('dislike'));
        $this->post->getReactions()->add((new ReactionPost())->setUtilisateur($currentUser)->setType('solidaire'));

        $this->assertSame('solidaire', $this->post->getUserReactionType($currentUser));
    }

    public function testGetUserReactionTypeReturnsNullWhenUserHasNoReaction(): void
    {
        $this->post->getReactions()->add(
            (new ReactionPost())->setUtilisateur($this->createUserWithId(8))->setType('like')
        );

        $this->assertNull($this->post->getUserReactionType($this->createUserWithId(7)));
    }

    public function testGetReactionItems(): void
    {
        $user = $this->createUserWithId(12);
        $this->post->getReactions()->add((new ReactionPost())->setUtilisateur($user)->setType('like'));
        $this->post->getReactions()->add((new ReactionPost())->setUtilisateur($this->createUserWithId(13))->setType('like'));

        $items = $this->post->getReactionItems($user);

        $this->assertCount(5, $items);
        $this->assertSame('like', $items[0]['type']);
        $this->assertSame('Like', $items[0]['label']);
        $this->assertSame(2, $items[0]['count']);
        $this->assertTrue($items[0]['active']);
        $this->assertSame('dislike', $items[1]['type']);
        $this->assertFalse($items[1]['active']);
    }

    public function testReactionItemsExposeTheWholeReactionPalette(): void
    {
        $currentUser = $this->createUserWithId(21);

        $this->post->getReactions()->add((new ReactionPost())->setUtilisateur($currentUser)->setType('like'));
        $this->post->getReactions()->add((new ReactionPost())->setUtilisateur($this->createUserWithId(22))->setType('like'));
        $this->post->getReactions()->add((new ReactionPost())->setUtilisateur($this->createUserWithId(23))->setType('dislike'));
        $this->post->getReactions()->add((new ReactionPost())->setUtilisateur($this->createUserWithId(24))->setType('solidaire'));
        $this->post->getReactions()->add((new ReactionPost())->setUtilisateur($this->createUserWithId(25))->setType('encolere'));
        $this->post->getReactions()->add((new ReactionPost())->setUtilisateur($this->createUserWithId(26))->setType('triste'));

        $items = $this->post->getReactionItems($currentUser);

        $this->assertCount(5, $items);
        $this->assertSame(['like', 'dislike', 'solidaire', 'encolere', 'triste'], array_column($items, 'type'));
        $this->assertSame([2, 1, 1, 1, 1], array_column($items, 'count'));
        $this->assertSame([true, false, false, false, false], array_column($items, 'active'));

        foreach ($items as $item) {
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('emoji', $item);
            $this->assertNotSame('', $item['label']);
            $this->assertNotSame('', $item['emoji']);
        }
    }

    private function createUserWithId(int $id): Utilisateur
    {
        $user = new Utilisateur();
        $reflection = new \ReflectionProperty(Utilisateur::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }
}
