<?php

namespace App\Tests\Unit\Marketplace;

use App\Entity\Marketplace\Commande;
use App\Entity\Marketplace\DetailCommande;
use App\Entity\Marketplace\Equipement;
use PHPUnit\Framework\TestCase;

class DetailCommandeTest extends TestCase
{
    private DetailCommande $detail;

    protected function setUp(): void
    {
        $this->detail = new DetailCommande();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->detail->getId());
        $this->assertNull($this->detail->getCommande());
        $this->assertNull($this->detail->getEquipement());
        $this->assertSame(0, $this->detail->getQuantite());
        $this->assertNull($this->detail->getPrixUnitaire());
        $this->assertNull($this->detail->getSousTotal());
    }

    public function testSetAndGetCommande(): void
    {
        $commande = new Commande();
        $result = $this->detail->setCommande($commande);
        $this->assertSame($commande, $this->detail->getCommande());
        $this->assertSame($this->detail, $result);
    }

    public function testSetAndGetEquipement(): void
    {
        $equipement = new Equipement();
        $equipement->setNom('Scie circulaire');
        $this->detail->setEquipement($equipement);
        $this->assertSame($equipement, $this->detail->getEquipement());
    }

    public function testSetAndGetQuantite(): void
    {
        $this->detail->setQuantite(5);
        $this->assertSame(5, $this->detail->getQuantite());
    }

    public function testSetAndGetPrixUnitaire(): void
    {
        $this->detail->setPrixUnitaire('250.00');
        $this->assertSame('250.00', $this->detail->getPrixUnitaire());
    }

    public function testSetAndGetSousTotal(): void
    {
        $this->detail->setSousTotal('1250.00');
        $this->assertSame('1250.00', $this->detail->getSousTotal());
    }

    public function testFluentSetters(): void
    {
        $commande = new Commande();
        $equipement = new Equipement();

        $result = $this->detail
            ->setCommande($commande)
            ->setEquipement($equipement)
            ->setQuantite(3)
            ->setPrixUnitaire('100.00')
            ->setSousTotal('300.00');

        $this->assertSame($this->detail, $result);
        $this->assertSame($commande, $this->detail->getCommande());
        $this->assertSame($equipement, $this->detail->getEquipement());
        $this->assertSame(3, $this->detail->getQuantite());
        $this->assertSame('100.00', $this->detail->getPrixUnitaire());
        $this->assertSame('300.00', $this->detail->getSousTotal());
    }

    // ── Edge cases — quantite ─────────────────────────────────────────────────

    public function testSetQuantiteToZero(): void
    {
        $this->detail->setQuantite(0);
        $this->assertSame(0, $this->detail->getQuantite());
    }

    public function testSetQuantiteToNegative(): void
    {
        // PHP level stores it; validator would reject in real usage
        $this->detail->setQuantite(-1);
        $this->assertSame(-1, $this->detail->getQuantite());
    }

    public function testSetQuantiteToLargeNumber(): void
    {
        $this->detail->setQuantite(99999);
        $this->assertSame(99999, $this->detail->getQuantite());
    }

    // ── Edge cases — prixUnitaire ─────────────────────────────────────────────

    public function testSetPrixUnitaireZero(): void
    {
        $this->detail->setPrixUnitaire('0.00');
        $this->assertSame('0.00', $this->detail->getPrixUnitaire());
    }

    public function testSetPrixUnitaireNegative(): void
    {
        // PHP level stores it; @Assert\Positive would reject in real usage
        $this->detail->setPrixUnitaire('-50.00');
        $this->assertSame('-50.00', $this->detail->getPrixUnitaire());
    }

    public function testSetPrixUnitaireLargeValue(): void
    {
        $this->detail->setPrixUnitaire('99999999.99');
        $this->assertSame('99999999.99', $this->detail->getPrixUnitaire());
    }

    // ── Edge cases — sousTotal ────────────────────────────────────────────────

    public function testSetSousTotalZero(): void
    {
        $this->detail->setSousTotal('0.00');
        $this->assertSame('0.00', $this->detail->getSousTotal());
    }

    public function testSetSousTotalDoesNotMatchQuantiteTimesPrice(): void
    {
        // Entity does not compute sousTotal automatically — it is set externally
        $this->detail->setQuantite(3);
        $this->detail->setPrixUnitaire('100.00');
        $this->detail->setSousTotal('999.00'); // intentionally wrong
        $this->assertSame('999.00', $this->detail->getSousTotal());
    }

    // ── Edge cases — relations ────────────────────────────────────────────────

    public function testSetCommandeToNull(): void
    {
        $this->detail->setCommande(new Commande());
        $this->detail->setCommande(null);
        $this->assertNull($this->detail->getCommande());
    }

    public function testSetEquipementToNull(): void
    {
        $this->detail->setEquipement(new Equipement());
        $this->detail->setEquipement(null);
        $this->assertNull($this->detail->getEquipement());
    }

    public function testReplaceCommandeWithAnother(): void
    {
        $c1 = new Commande();
        $c2 = new Commande();
        $this->detail->setCommande($c1);
        $this->detail->setCommande($c2);
        $this->assertSame($c2, $this->detail->getCommande());
    }
}
