<?php

namespace App\Tests\Unit\Forum;

use App\Entity\Forum\Commentaire;
use App\Entity\Forum\ForumModerationAlert;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class ForumModerationAlertTest extends TestCase
{
    private ForumModerationAlert $alert;

    protected function setUp(): void
    {
        $this->alert = new ForumModerationAlert();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->alert->getId());
        $this->assertNull($this->alert->getCommentaire());
        $this->assertNull($this->alert->getUtilisateur());
        $this->assertNull($this->alert->getOriginalContent());
        $this->assertNull($this->alert->getMaskedContent());
        $this->assertSame([], $this->alert->getMatchedWords());
        $this->assertSame('pending', $this->alert->getStatus());
        $this->assertNull($this->alert->getCreatedAt());
        $this->assertNull($this->alert->getReviewedAt());
        $this->assertNull($this->alert->getNote());
    }

    public function testSetAndGetCommentaire(): void
    {
        $commentaire = new Commentaire();
        $result = $this->alert->setCommentaire($commentaire);

        $this->assertSame($commentaire, $this->alert->getCommentaire());
        $this->assertSame($this->alert, $result);

        $this->alert->setCommentaire(null);
        $this->assertNull($this->alert->getCommentaire());
    }

    public function testSetAndGetUtilisateur(): void
    {
        $user = new Utilisateur();
        $result = $this->alert->setUtilisateur($user);

        $this->assertSame($user, $this->alert->getUtilisateur());
        $this->assertSame($this->alert, $result);
    }

    public function testSetAndGetOriginalContent(): void
    {
        $this->alert->setOriginalContent('Message original');

        $this->assertSame('Message original', $this->alert->getOriginalContent());
    }

    public function testSetAndGetMaskedContent(): void
    {
        $this->alert->setMaskedContent('Message ***');

        $this->assertSame('Message ***', $this->alert->getMaskedContent());
    }

    public function testSetMatchedWordsCastsValuesAndReindexes(): void
    {
        $this->alert->setMatchedWords([2 => 'mot', 5 => 123]);

        $this->assertSame(['mot', '123'], $this->alert->getMatchedWords());
    }

    public function testSetAndGetStatus(): void
    {
        foreach (['pending', 'reviewed', 'ignored'] as $status) {
            $this->alert->setStatus($status);
            $this->assertSame($status, $this->alert->getStatus());
        }
    }

    public function testSetAndGetCreatedAt(): void
    {
        $date = new \DateTime('2026-04-24 11:30:00');
        $this->alert->initializeTimestamp($date);

        $this->assertSame($date, $this->alert->getCreatedAt());
    }

    public function testSetAndGetReviewedAt(): void
    {
        $date = new \DateTime('2026-04-25 12:00:00');
        $this->alert->setReviewedAt($date);
        $this->assertSame($date, $this->alert->getReviewedAt());

        $this->alert->setReviewedAt(null);
        $this->assertNull($this->alert->getReviewedAt());
    }

    public function testSetAndGetNote(): void
    {
        $this->alert->setNote('A verifier');
        $this->assertSame('A verifier', $this->alert->getNote());

        $this->alert->setNote(null);
        $this->assertNull($this->alert->getNote());
    }

    public function testAlertCanRepresentAFullModerationReview(): void
    {
        $commentaire = new Commentaire();
        $user = new Utilisateur();
        $createdAt = new \DateTime('2026-04-24 11:30:00');
        $reviewedAt = new \DateTime('2026-04-25 12:00:00');

        $this->alert
            ->setCommentaire($commentaire)
            ->setUtilisateur($user)
            ->setOriginalContent('Message avec contenu sensible.')
            ->setMaskedContent('Message avec contenu *******.')
            ->setMatchedWords([0 => 'sensible', 3 => 999])
            ->setStatus('reviewed')
            ->initializeTimestamp($createdAt)
            ->setReviewedAt($reviewedAt)
            ->setNote('Texte modere apres verification.');

        $this->assertSame($commentaire, $this->alert->getCommentaire());
        $this->assertSame($user, $this->alert->getUtilisateur());
        $this->assertSame('Message avec contenu sensible.', $this->alert->getOriginalContent());
        $this->assertSame('Message avec contenu *******.', $this->alert->getMaskedContent());
        $this->assertSame(['sensible', '999'], $this->alert->getMatchedWords());
        $this->assertSame('reviewed', $this->alert->getStatus());
        $this->assertSame($createdAt, $this->alert->getCreatedAt());
        $this->assertSame($reviewedAt, $this->alert->getReviewedAt());
        $this->assertSame('Texte modere apres verification.', $this->alert->getNote());
    }
}
