<?php

namespace App\Tests\Unit\Event;

use App\Entity\Event\Accompagnant;
use App\Entity\Event\Evenement;
use App\Entity\Event\Participation;
use App\Entity\Event\StatutParticipation;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class ParticipationTest extends TestCase
{
    private Participation $participation;

    protected function setUp(): void
    {
        $this->participation = new Participation();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->participation->getIdParticipation());
        $this->assertNull($this->participation->getEvenement());
        $this->assertNull($this->participation->getUtilisateur());
        $this->assertSame('en_attente', $this->participation->getStatut());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->participation->getDateInscription());
        $this->assertNull($this->participation->getDateAnnulation());
        $this->assertSame(0, $this->participation->getNombreAccompagnants());
        $this->assertNull($this->participation->getCommentaire());
        $this->assertNull($this->participation->getCodeParticipation());
        $this->assertCount(0, $this->participation->getAccompagnants());
    }

    public function testSetAndGetEvenement(): void
    {
        $event = new Evenement();
        $result = $this->participation->setEvenement($event);
        $this->assertSame($event, $this->participation->getEvenement());
        $this->assertSame($this->participation, $result);
    }

    public function testSetAndGetUtilisateur(): void
    {
        $user = new Utilisateur();
        $result = $this->participation->setUtilisateur($user);
        $this->assertSame($user, $this->participation->getUtilisateur());
        $this->assertSame($this->participation, $result);
    }

    public function testSetAndGetStatut(): void
    {
        foreach (['en_attente', 'confirme', 'annule'] as $statut) {
            $this->participation->setStatut($statut);
            $this->assertSame($statut, $this->participation->getStatut());
        }
    }

    public function testGetStatutEnum(): void
    {
        $this->participation->setStatut('confirme');
        $this->assertSame(StatutParticipation::CONFIRME, $this->participation->getStatutEnum());
    }

    public function testGetStatutEnumReturnsNullForInvalid(): void
    {
        $this->participation->setStatut('inexistant');
        $this->assertNull($this->participation->getStatutEnum());
    }

    public function testSetAndGetDateInscription(): void
    {
        $d = new \DateTime('2026-04-01');
        $this->participation->setDateInscription($d);
        $this->assertSame($d, $this->participation->getDateInscription());
    }

    public function testSetAndGetDateAnnulation(): void
    {
        $d = new \DateTime('2026-04-10');
        $this->participation->setDateAnnulation($d);
        $this->assertSame($d, $this->participation->getDateAnnulation());

        $this->participation->setDateAnnulation(null);
        $this->assertNull($this->participation->getDateAnnulation());
    }

    public function testSetAndGetNombreAccompagnants(): void
    {
        $this->participation->setNombreAccompagnants(3);
        $this->assertSame(3, $this->participation->getNombreAccompagnants());
    }

    public function testGetTotalPersonnesIsOnePlusAccompagnants(): void
    {
        $this->participation->setNombreAccompagnants(0);
        $this->assertSame(1, $this->participation->getTotalPersonnes());

        $this->participation->setNombreAccompagnants(4);
        $this->assertSame(5, $this->participation->getTotalPersonnes());
    }

    public function testSetAndGetCommentaire(): void
    {
        $this->participation->setCommentaire('Régime végétarien');
        $this->assertSame('Régime végétarien', $this->participation->getCommentaire());
    }

    public function testSetAndGetCodeParticipation(): void
    {
        $this->participation->setCodeParticipation('PART-A1B2C');
        $this->assertSame('PART-A1B2C', $this->participation->getCodeParticipation());
    }

    public function testGenererCodeFormat(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $code = Participation::genererCode();
            $this->assertSame(10, strlen($code), "Code doit faire 10 caractères : {$code}");
            $this->assertStringStartsWith('PART-', $code);
            $this->assertMatchesRegularExpression('/^PART-[A-Z0-9]{5}$/', $code);
        }
    }

    public function testGenererCodeProducesDifferentValues(): void
    {
        $codes = [];
        for ($i = 0; $i < 50; $i++) {
            $codes[] = Participation::genererCode();
        }
        // Au moins 90 % d'unicité (50 tirages sur 36^5 ≈ 60M combinaisons → quasi sûrement uniques)
        $unique = count(array_unique($codes));
        $this->assertGreaterThanOrEqual(45, $unique);
    }

    public function testAddAccompagnantSetsBackReference(): void
    {
        $acc = new Accompagnant();
        $this->participation->addAccompagnant($acc);

        $this->assertCount(1, $this->participation->getAccompagnants());
        $this->assertSame($this->participation, $acc->getParticipation());
    }

    public function testAddAccompagnantIsIdempotent(): void
    {
        $acc = new Accompagnant();
        $this->participation->addAccompagnant($acc);
        $this->participation->addAccompagnant($acc);

        $this->assertCount(1, $this->participation->getAccompagnants());
    }

    public function testRemoveAccompagnantClearsBackReference(): void
    {
        $acc = new Accompagnant();
        $this->participation->addAccompagnant($acc);
        $this->participation->removeAccompagnant($acc);

        $this->assertCount(0, $this->participation->getAccompagnants());
        $this->assertNull($acc->getParticipation());
    }
}
