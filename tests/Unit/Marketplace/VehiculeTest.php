<?php

namespace App\Tests\Unit\Marketplace;

use App\Entity\Marketplace\Categorie;
use App\Entity\Marketplace\Vehicule;
use PHPUnit\Framework\TestCase;

class VehiculeTest extends TestCase
{
    private Vehicule $vehicule;

    protected function setUp(): void
    {
        $this->vehicule = new Vehicule();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->vehicule->getId());
        $this->assertNull($this->vehicule->getNom());
        $this->assertNull($this->vehicule->getCategorie());
        $this->assertNull($this->vehicule->getDescription());
        $this->assertNull($this->vehicule->getMarque());
        $this->assertNull($this->vehicule->getModele());
        $this->assertNull($this->vehicule->getImmatriculation());
        $this->assertNull($this->vehicule->getPrixJour());
        $this->assertNull($this->vehicule->getPrixSemaine());
        $this->assertNull($this->vehicule->getPrixMois());
        $this->assertSame('0.00', $this->vehicule->getCaution());
        $this->assertNull($this->vehicule->getImageUrl());
        $this->assertTrue($this->vehicule->isDisponible());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->vehicule->getDateCreation());
    }

    public function testSetAndGetNom(): void
    {
        $result = $this->vehicule->setNom('Toyota Hilux');
        $this->assertSame('Toyota Hilux', $this->vehicule->getNom());
        $this->assertSame($this->vehicule, $result);
    }

    public function testSetAndGetCategorie(): void
    {
        $categorie = new Categorie();
        $categorie->setNom('Utilitaires');
        $this->vehicule->setCategorie($categorie);
        $this->assertSame($categorie, $this->vehicule->getCategorie());
    }

    public function testSetAndGetMarqueModele(): void
    {
        $this->vehicule->setMarque('Toyota');
        $this->vehicule->setModele('Hilux 2024');
        $this->assertSame('Toyota', $this->vehicule->getMarque());
        $this->assertSame('Hilux 2024', $this->vehicule->getModele());
    }

    public function testSetAndGetImmatriculation(): void
    {
        $this->vehicule->setImmatriculation('123 TU 4567');
        $this->assertSame('123 TU 4567', $this->vehicule->getImmatriculation());
    }

    public function testSetAndGetPricing(): void
    {
        $this->vehicule->setPrixJour('150.00');
        $this->vehicule->setPrixSemaine('900.00');
        $this->vehicule->setPrixMois('3000.00');
        $this->vehicule->setCaution('500.00');

        $this->assertSame('150.00', $this->vehicule->getPrixJour());
        $this->assertSame('900.00', $this->vehicule->getPrixSemaine());
        $this->assertSame('3000.00', $this->vehicule->getPrixMois());
        $this->assertSame('500.00', $this->vehicule->getCaution());
    }

    public function testSetAndGetDescription(): void
    {
        $this->vehicule->setDescription('Véhicule utilitaire tout-terrain');
        $this->assertSame('Véhicule utilitaire tout-terrain', $this->vehicule->getDescription());
    }

    public function testSetAndGetImageUrl(): void
    {
        $this->vehicule->setImageUrl('/uploads/marketplace/vehicules/hilux.jpg');
        $this->assertSame('/uploads/marketplace/vehicules/hilux.jpg', $this->vehicule->getImageUrl());
    }

    public function testSetAndGetDisponible(): void
    {
        $this->vehicule->setDisponible(false);
        $this->assertFalse($this->vehicule->isDisponible());
    }

    public function testSetAndGetDateCreation(): void
    {
        $date = new \DateTime('2025-03-20');
        $this->vehicule->setDateCreation($date);
        $this->assertSame($date, $this->vehicule->getDateCreation());
    }

    public function testToString(): void
    {
        $this->assertSame('', (string) $this->vehicule);
        $this->vehicule->setNom('Camion Renault');
        $this->assertSame('Camion Renault', (string) $this->vehicule);
    }

    public function testNullablePricing(): void
    {
        $this->vehicule->setPrixSemaine(null);
        $this->assertNull($this->vehicule->getPrixSemaine());

        $this->vehicule->setPrixMois(null);
        $this->assertNull($this->vehicule->getPrixMois());
    }

    public function testFluentSetters(): void
    {
        $result = $this->vehicule
            ->setNom('Test')
            ->setMarque('Marque')
            ->setModele('Modele')
            ->setPrixJour('100.00')
            ->setDisponible(true);

        $this->assertSame($this->vehicule, $result);
    }

    // ── Edge cases — prixJour ─────────────────────────────────────────────────

    public function testSetPrixJourToZero(): void
    {
        $this->vehicule->setPrixJour('0.00');
        $this->assertSame('0.00', $this->vehicule->getPrixJour());
    }

    public function testSetPrixJourToNegative(): void
    {
        // PHP level stores it; @Assert\Positive would reject in real usage
        $this->vehicule->setPrixJour('-10.00');
        $this->assertSame('-10.00', $this->vehicule->getPrixJour());
    }

    public function testSetPrixJourToNull(): void
    {
        $this->vehicule->setPrixJour(null);
        $this->assertNull($this->vehicule->getPrixJour());
    }

    public function testSetPrixJourLargeValue(): void
    {
        $this->vehicule->setPrixJour('99999.99');
        $this->assertSame('99999.99', $this->vehicule->getPrixJour());
    }

    // ── Edge cases — caution ──────────────────────────────────────────────────

    public function testCautionDefaultValue(): void
    {
        $fresh = new \App\Entity\Marketplace\Vehicule();
        $this->assertSame('0.00', $fresh->getCaution());
    }

    public function testSetCautionNegative(): void
    {
        // PHP level stores it; @Assert\PositiveOrZero would reject in real usage
        $this->vehicule->setCaution('-50.00');
        $this->assertSame('-50.00', $this->vehicule->getCaution());
    }

    // ── Edge cases — nullable fields ──────────────────────────────────────────

    public function testSetCategorieToNull(): void
    {
        $this->vehicule->setCategorie(new \App\Entity\Marketplace\Categorie());
        $this->vehicule->setCategorie(null);
        $this->assertNull($this->vehicule->getCategorie());
    }

    public function testSetImmatriculationToNull(): void
    {
        $this->vehicule->setImmatriculation('123TU4567');
        $this->vehicule->setImmatriculation(null);
        $this->assertNull($this->vehicule->getImmatriculation());
    }

    public function testSetDescriptionToNull(): void
    {
        $this->vehicule->setDescription('Belle moto');
        $this->vehicule->setDescription(null);
        $this->assertNull($this->vehicule->getDescription());
    }

    public function testSetImageUrlToNull(): void
    {
        $this->vehicule->setImageUrl('vehicule.jpg');
        $this->vehicule->setImageUrl(null);
        $this->assertNull($this->vehicule->getImageUrl());
    }

    public function testSetMarqueToNull(): void
    {
        $this->vehicule->setMarque('Toyota');
        $this->vehicule->setMarque(null);
        $this->assertNull($this->vehicule->getMarque());
    }

    public function testSetModeleToNull(): void
    {
        $this->vehicule->setModele('Hilux');
        $this->vehicule->setModele(null);
        $this->assertNull($this->vehicule->getModele());
    }

    // ── Edge cases — nom ──────────────────────────────────────────────────────

    public function testSetNomToNull(): void
    {
        $this->vehicule->setNom('Tracteur');
        $this->vehicule->setNom(null);
        $this->assertNull($this->vehicule->getNom());
    }

    public function testSetNomWithMaxLength(): void
    {
        $long = str_repeat('V', 200);
        $this->vehicule->setNom($long);
        $this->assertSame(200, strlen($this->vehicule->getNom()));
    }

    // ── Edge cases — __toString ───────────────────────────────────────────────

    public function testToStringWithNullNom(): void
    {
        $fresh = new \App\Entity\Marketplace\Vehicule();
        $this->assertSame('', (string) $fresh);
    }

    // ── Edge cases — dateCreation ─────────────────────────────────────────────

    public function testDateCreationIsSetInConstructor(): void
    {
        $fresh = new \App\Entity\Marketplace\Vehicule();
        $this->assertInstanceOf(\DateTimeInterface::class, $fresh->getDateCreation());
    }

    // ── Edge cases — immatriculation format ───────────────────────────────────

    public function testSetImmatriculationWithAnyString(): void
    {
        // Entity stores any string; regex validation is in Symfony groups
        $this->vehicule->setImmatriculation('INVALID');
        $this->assertSame('INVALID', $this->vehicule->getImmatriculation());
    }
}
