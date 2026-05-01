<?php

namespace App\Tests\Unit\Marketplace;

use App\Entity\Marketplace\Categorie;
use App\Entity\Marketplace\Terrain;
use PHPUnit\Framework\TestCase;

class TerrainTest extends TestCase
{
    private Terrain $terrain;

    protected function setUp(): void
    {
        $this->terrain = new Terrain();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->terrain->getId());
        $this->assertNull($this->terrain->getTitre());
        $this->assertNull($this->terrain->getCategorie());
        $this->assertNull($this->terrain->getDescription());
        $this->assertNull($this->terrain->getSuperficieHectares());
        $this->assertNull($this->terrain->getVille());
        $this->assertNull($this->terrain->getAdresse());
        $this->assertNull($this->terrain->getPrixMois());
        $this->assertNull($this->terrain->getPrixAnnee());
        $this->assertSame('0.00', $this->terrain->getCaution());
        $this->assertNull($this->terrain->getImageUrl());
        $this->assertTrue($this->terrain->isDisponible());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->terrain->getDateCreation());
    }

    public function testSetAndGetTitre(): void
    {
        $result = $this->terrain->setTitre('Terrain agricole Nabeul');
        $this->assertSame('Terrain agricole Nabeul', $this->terrain->getTitre());
        $this->assertSame($this->terrain, $result);
    }

    public function testSetAndGetCategorie(): void
    {
        $categorie = new Categorie();
        $categorie->setNom('Terres arables');
        $this->terrain->setCategorie($categorie);
        $this->assertSame($categorie, $this->terrain->getCategorie());
    }

    public function testSetAndGetSuperficieHectares(): void
    {
        $this->terrain->setSuperficieHectares('12.50');
        $this->assertSame('12.50', $this->terrain->getSuperficieHectares());
    }

    public function testSetAndGetVille(): void
    {
        $this->terrain->setVille('Nabeul');
        $this->assertSame('Nabeul', $this->terrain->getVille());
    }

    public function testSetAndGetAdresse(): void
    {
        $this->terrain->setAdresse('Route de Hammamet, km 5');
        $this->assertSame('Route de Hammamet, km 5', $this->terrain->getAdresse());
    }

    public function testSetAndGetPricing(): void
    {
        $this->terrain->setPrixMois('2500.00');
        $this->terrain->setPrixAnnee('25000.00');
        $this->terrain->setCaution('5000.00');

        $this->assertSame('2500.00', $this->terrain->getPrixMois());
        $this->assertSame('25000.00', $this->terrain->getPrixAnnee());
        $this->assertSame('5000.00', $this->terrain->getCaution());
    }

    public function testPrixMoisNullable(): void
    {
        $this->terrain->setPrixMois(null);
        $this->assertNull($this->terrain->getPrixMois());
    }

    public function testSetAndGetImageUrl(): void
    {
        $this->terrain->setImageUrl('/uploads/marketplace/terrains/terrain1.jpg');
        $this->assertSame('/uploads/marketplace/terrains/terrain1.jpg', $this->terrain->getImageUrl());
    }

    public function testSetAndGetDisponible(): void
    {
        $this->terrain->setDisponible(false);
        $this->assertFalse($this->terrain->isDisponible());

        $this->terrain->setDisponible(true);
        $this->assertTrue($this->terrain->isDisponible());
    }

    public function testSetAndGetDescription(): void
    {
        $this->terrain->setDescription('Terrain fertile adapté à l\'agriculture');
        $this->assertSame('Terrain fertile adapté à l\'agriculture', $this->terrain->getDescription());
    }

    public function testSetAndGetDateCreation(): void
    {
        $date = new \DateTime('2025-07-01');
        $this->terrain->setDateCreation($date);
        $this->assertSame($date, $this->terrain->getDateCreation());
    }

    public function testToString(): void
    {
        $this->assertSame('', (string) $this->terrain);
        $this->terrain->setTitre('Terrain Sousse');
        $this->assertSame('Terrain Sousse', (string) $this->terrain);
    }

    public function testFluentSetters(): void
    {
        $result = $this->terrain
            ->setTitre('Test')
            ->setVille('Tunis')
            ->setSuperficieHectares('5.00')
            ->setPrixAnnee('10000.00')
            ->setDisponible(true);

        $this->assertSame($this->terrain, $result);
    }

    // ── Edge cases — superficieHectares ──────────────────────────────────────

    public function testSetSuperficieHectaresToZero(): void
    {
        $this->terrain->setSuperficieHectares('0.00');
        $this->assertSame('0.00', $this->terrain->getSuperficieHectares());
    }

    public function testSetSuperficieHectaresToNegative(): void
    {
        // PHP level stores it; @Assert\Positive would reject in real usage
        $this->terrain->setSuperficieHectares('-1.00');
        $this->assertSame('-1.00', $this->terrain->getSuperficieHectares());
    }

    public function testSetSuperficieHectaresToLargeValue(): void
    {
        $this->terrain->setSuperficieHectares('99999.99');
        $this->assertSame('99999.99', $this->terrain->getSuperficieHectares());
    }

    // ── Edge cases — pricing ──────────────────────────────────────────────────

    public function testCautionDefaultValue(): void
    {
        $fresh = new \App\Entity\Marketplace\Terrain();
        $this->assertSame('0.00', $fresh->getCaution());
    }

    public function testSetPrixAnneeNegative(): void
    {
        // PHP level stores it; validator would reject
        $this->terrain->setPrixAnnee('-5000.00');
        $this->assertSame('-5000.00', $this->terrain->getPrixAnnee());
    }

    public function testSetPrixMoisZero(): void
    {
        $this->terrain->setPrixMois('0.00');
        $this->assertSame('0.00', $this->terrain->getPrixMois());
    }

    // ── Edge cases — nullable fields ──────────────────────────────────────────

    public function testSetAdresseToNull(): void
    {
        $this->terrain->setAdresse('123 Rue Test');
        $this->terrain->setAdresse(null);
        $this->assertNull($this->terrain->getAdresse());
    }

    public function testSetImageUrlToNull(): void
    {
        $this->terrain->setImageUrl('terrain.jpg');
        $this->terrain->setImageUrl(null);
        $this->assertNull($this->terrain->getImageUrl());
    }

    public function testSetDescriptionToNull(): void
    {
        $this->terrain->setDescription('Une description');
        $this->terrain->setDescription(null);
        $this->assertNull($this->terrain->getDescription());
    }

    public function testSetCategorieToNull(): void
    {
        $this->terrain->setCategorie(new \App\Entity\Marketplace\Categorie());
        $this->terrain->setCategorie(null);
        $this->assertNull($this->terrain->getCategorie());
    }

    // ── Edge cases — titre ────────────────────────────────────────────────────

    public function testSetTitreToNull(): void
    {
        $this->terrain->setTitre('Test');
        $this->terrain->setTitre(null);
        $this->assertNull($this->terrain->getTitre());
    }

    public function testSetTitreWithMaxLength(): void
    {
        $long = str_repeat('T', 200);
        $this->terrain->setTitre($long);
        $this->assertSame(200, strlen($this->terrain->getTitre()));
    }

    // ── Edge cases — dateCreation ─────────────────────────────────────────────

    public function testDateCreationIsSetInConstructor(): void
    {
        $fresh = new \App\Entity\Marketplace\Terrain();
        $this->assertInstanceOf(\DateTimeInterface::class, $fresh->getDateCreation());
    }
}
