<?php

namespace App\Tests\Unit\Maladie;

use App\Entity\Maladie\Maladie;
use App\Entity\Maladie\MaladieCase;
use App\Entity\Maladie\MaladieCaseUpdate;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class MaladieCaseTest extends TestCase
{
    private MaladieCase $case;

    protected function setUp(): void
    {
        $this->case = new MaladieCase();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->case->getId());
        $this->assertNull($this->case->getMaladie());
        $this->assertNull($this->case->getUtilisateur());
        $this->assertNull($this->case->getCulture());
        $this->assertNull($this->case->getParcelle());
        $this->assertNull($this->case->getSymptomes());
        $this->assertSame('ouvert', $this->case->getStatut());
        $this->assertTrue($this->case->isPublic());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->case->getCreatedAt());
        $this->assertNull($this->case->getUpdatedAt());
        $this->assertCount(0, $this->case->getUpdates());
    }

    public function testSetAndGetMaladie(): void
    {
        $maladie = new Maladie();
        $result = $this->case->setMaladie($maladie);

        $this->assertSame($maladie, $this->case->getMaladie());
        $this->assertSame($this->case, $result);

        $this->case->setMaladie(null);
        $this->assertNull($this->case->getMaladie());
    }

    public function testSetAndGetUtilisateur(): void
    {
        $user = new Utilisateur();
        $result = $this->case->setUtilisateur($user);

        $this->assertSame($user, $this->case->getUtilisateur());
        $this->assertSame($this->case, $result);

        $this->case->setUtilisateur(null);
        $this->assertNull($this->case->getUtilisateur());
    }

    public function testSetAndGetCultureAndParcelle(): void
    {
        $this->case->setCulture('Tomate');
        $this->case->setParcelle('Parcelle A');

        $this->assertSame('Tomate', $this->case->getCulture());
        $this->assertSame('Parcelle A', $this->case->getParcelle());

        $this->case->setCulture(null);
        $this->case->setParcelle(null);

        $this->assertNull($this->case->getCulture());
        $this->assertNull($this->case->getParcelle());
    }

    public function testSetAndGetSymptomes(): void
    {
        $this->case->setSymptomes('Feuilles jaunes et taches brunes.');

        $this->assertSame('Feuilles jaunes et taches brunes.', $this->case->getSymptomes());
    }

    public function testSetAndGetStatut(): void
    {
        foreach (['ouvert', 'en_cours', 'resolu', 'ferme'] as $statut) {
            $this->case->setStatut($statut);
            $this->assertSame($statut, $this->case->getStatut());
        }
    }

    public function testSetAndGetIsPublic(): void
    {
        $result = $this->case->setIsPublic(false);

        $this->assertFalse($this->case->isPublic());
        $this->assertSame($this->case, $result);

        $this->case->setIsPublic(true);
        $this->assertTrue($this->case->isPublic());
    }

    public function testLifecycleCallbacksSetDates(): void
    {
        $this->case->initializeTimestamp();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->case->getCreatedAt());

        $this->case->setUpdatedAtValue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->case->getUpdatedAt());
    }

    public function testUpdatesCollection(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->case->getUpdates());

        $this->case->getUpdates()->add(new MaladieCaseUpdate());
        $this->assertCount(1, $this->case->getUpdates());
    }

    public function testCaseCanBeConfiguredAndCleared(): void
    {
        $maladie = new Maladie();
        $user = new Utilisateur();
        $this->case
            ->setMaladie($maladie)
            ->setUtilisateur($user)
            ->setCulture('Tomate')
            ->setParcelle('Parcelle B')
            ->setSymptomes('Taches et feuilles qui jaunissent.')
            ->setStatut('en_cours')
            ->setIsPublic(false);

        $this->assertSame($maladie, $this->case->getMaladie());
        $this->assertSame($user, $this->case->getUtilisateur());
        $this->assertSame('Tomate', $this->case->getCulture());
        $this->assertSame('Parcelle B', $this->case->getParcelle());
        $this->assertSame('Taches et feuilles qui jaunissent.', $this->case->getSymptomes());
        $this->assertSame('en_cours', $this->case->getStatut());
        $this->assertFalse($this->case->isPublic());

        $this->case->setMaladie(null);
        $this->case->setUtilisateur(null);
        $this->case->setCulture(null);
        $this->case->setParcelle(null);

        $this->assertNull($this->case->getMaladie());
        $this->assertNull($this->case->getUtilisateur());
        $this->assertNull($this->case->getCulture());
        $this->assertNull($this->case->getParcelle());
    }
}
