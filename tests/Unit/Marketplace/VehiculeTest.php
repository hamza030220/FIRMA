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
}
