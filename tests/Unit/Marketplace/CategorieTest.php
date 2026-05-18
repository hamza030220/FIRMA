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

    // ── Edge cases — nom ──────────────────────────────────────────────────────

    public function testNomWithAccentedCharacters(): void
    {
        $this->categorie->setNom('Matériel agricole & équipements');
        $this->assertSame('Matériel agricole & équipements', $this->categorie->getNom());
    }

    public function testNomWithMaxLength(): void
    {
        $long = str_repeat('A', 100);
        $this->categorie->setNom($long);
        $this->assertSame(100, strlen($this->categorie->getNom()));
    }

    public function testSetNomFluentInterface(): void
    {
        $result = $this->categorie->setNom('Test');
        $this->assertSame($this->categorie, $result);
    }

    // ── Edge cases — typeProduit ──────────────────────────────────────────────

    public function testTypeProduitWithUnknownValue(): void
    {
        // PHP level stores anything; Symfony validator restricts values
        $this->categorie->setTypeProduit('inconnu');
        $this->assertSame('inconnu', $this->categorie->getTypeProduit());
    }

    public function testTypeProduitFluentInterface(): void
    {
        $result = $this->categorie->setTypeProduit('equipement');
        $this->assertSame($this->categorie, $result);
    }

    // ── Edge cases — description ──────────────────────────────────────────────

    public function testDescriptionWithLongText(): void
    {
        $long = str_repeat('x', 2000);
        $this->categorie->setDescription($long);
        $this->assertSame(2000, strlen($this->categorie->getDescription()));
    }

    // ── Edge cases — __toString ───────────────────────────────────────────────

    public function testToStringWithAccentedNom(): void
    {
        $this->categorie->setNom('Véhicules lourds');
        $this->assertSame('Véhicules lourds', (string) $this->categorie);
    }

    public function testToStringReturnsEmptyStringInitially(): void
    {
        $this->assertSame('', (string) $this->categorie);
    }

    // ── Edge cases — collections ──────────────────────────────────────────────

    public function testCollectionsStartEmpty(): void
    {
        $this->assertCount(0, $this->categorie->getEquipements());
        $this->assertCount(0, $this->categorie->getVehicules());
        $this->assertCount(0, $this->categorie->getTerrains());
    }

    public function testEachCategorieInstanceHasIndependentCollections(): void
    {
        $cat2 = new Categorie();
        $cat2->getEquipements()->add(new Equipement());

        $this->assertCount(0, $this->categorie->getEquipements());
        $this->assertCount(1, $cat2->getEquipements());
    }
}
