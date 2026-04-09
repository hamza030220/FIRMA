<?php

namespace App\Tests\Unit\Marketplace;

use App\Entity\Marketplace\Categorie;
use App\Entity\Marketplace\Equipement;
use App\Entity\Marketplace\Vehicule;
use App\Entity\Marketplace\Terrain;
use PHPUnit\Framework\TestCase;

class CategorieTest extends TestCase
{
    private Categorie $categorie;

    protected function setUp(): void
    {
        $this->categorie = new Categorie();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->categorie->getId());
        $this->assertNull($this->categorie->getNom());
        $this->assertNull($this->categorie->getTypeProduit());
        $this->assertNull($this->categorie->getDescription());
        $this->assertCount(0, $this->categorie->getEquipements());
        $this->assertCount(0, $this->categorie->getVehicules());
        $this->assertCount(0, $this->categorie->getTerrains());
    }

    public function testSetAndGetNom(): void
    {
        $result = $this->categorie->setNom('Outillage');
        $this->assertSame('Outillage', $this->categorie->getNom());
        $this->assertSame($this->categorie, $result);
    }

    public function testSetAndGetTypeProduit(): void
    {
        $this->categorie->setTypeProduit('equipement');
        $this->assertSame('equipement', $this->categorie->getTypeProduit());

        $this->categorie->setTypeProduit('vehicule');
        $this->assertSame('vehicule', $this->categorie->getTypeProduit());

        $this->categorie->setTypeProduit('terrain');
        $this->assertSame('terrain', $this->categorie->getTypeProduit());
    }

    public function testSetAndGetDescription(): void
    {
        $this->categorie->setDescription('Une catégorie de test');
        $this->assertSame('Une catégorie de test', $this->categorie->getDescription());
    }

    public function testDescriptionNullable(): void
    {
        $this->categorie->setDescription('Texte');
        $this->categorie->setDescription(null);
        $this->assertNull($this->categorie->getDescription());
    }

    public function testToString(): void
    {
        $this->assertSame('', (string) $this->categorie);
        $this->categorie->setNom('Machines');
        $this->assertSame('Machines', (string) $this->categorie);
    }

    public function testCollectionsAreInitialized(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->categorie->getEquipements());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->categorie->getVehicules());
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->categorie->getTerrains());
    }
}
