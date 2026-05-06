<?php

namespace App\Tests\Unit\Maladie;

use App\Entity\Maladie\SolutionTraitement;
use App\Entity\Maladie\SolutionTraitementVote;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class SolutionTraitementVoteTest extends TestCase
{
    private SolutionTraitementVote $vote;

    protected function setUp(): void
    {
        $this->vote = new SolutionTraitementVote();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->vote->getId());
        $this->assertNull($this->vote->getSolutionTraitement());
        $this->assertNull($this->vote->getUtilisateur());
        $this->assertNull($this->vote->getType());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->vote->getCreatedAt());
    }

    public function testSetAndGetSolutionTraitement(): void
    {
        $solution = new SolutionTraitement();
        $result = $this->vote->setSolutionTraitement($solution);

        $this->assertSame($solution, $this->vote->getSolutionTraitement());
        $this->assertSame($this->vote, $result);

        $this->vote->setSolutionTraitement(null);
        $this->assertNull($this->vote->getSolutionTraitement());
    }

    public function testSetAndGetUtilisateur(): void
    {
        $user = new Utilisateur();
        $result = $this->vote->setUtilisateur($user);

        $this->assertSame($user, $this->vote->getUtilisateur());
        $this->assertSame($this->vote, $result);

        $this->vote->setUtilisateur(null);
        $this->assertNull($this->vote->getUtilisateur());
    }

    public function testSetAndGetType(): void
    {
        foreach (['up', 'down'] as $type) {
            $this->vote->setType($type);
            $this->assertSame($type, $this->vote->getType());
        }
    }

    public function testVoteCanBeConfiguredEndToEnd(): void
    {
        $solution = new SolutionTraitement();
        $user = new Utilisateur();

        $this->vote
            ->setSolutionTraitement($solution)
            ->setUtilisateur($user)
            ->setType('up');

        $this->assertSame($solution, $this->vote->getSolutionTraitement());
        $this->assertSame($user, $this->vote->getUtilisateur());
        $this->assertSame('up', $this->vote->getType());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->vote->getCreatedAt());
    }
}
