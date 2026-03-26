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
}
