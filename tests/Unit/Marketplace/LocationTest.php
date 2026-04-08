<?php

namespace App\Tests\Unit\Marketplace;

use App\Entity\Marketplace\Location;
use App\Entity\Marketplace\Terrain;
use App\Entity\Marketplace\Vehicule;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class LocationTest extends TestCase
{
    private Location $location;

    protected function setUp(): void
    {
        $this->location = new Location();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->location->getId());
        $this->assertNull($this->location->getUtilisateur());
        $this->assertNull($this->location->getTypeLocation());
        $this->assertNull($this->location->getVehicule());
        $this->assertNull($this->location->getTerrain());
        $this->assertNull($this->location->getNumeroLocation());
        $this->assertNull($this->location->getDateDebut());
        $this->assertNull($this->location->getDateFin());
        $this->assertNull($this->location->getDureeJours());
        $this->assertNull($this->location->getPrixTotal());
        $this->assertSame('0.00', $this->location->getCaution());
        $this->assertSame('en_attente', $this->location->getStatut());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->location->getDateReservation());
        $this->assertNull($this->location->getNotes());
    }

    public function testSetAndGetUtilisateur(): void
    {
        $user = new Utilisateur();
        $result = $this->location->setUtilisateur($user);
        $this->assertSame($user, $this->location->getUtilisateur());
        $this->assertSame($this->location, $result);
    }

    public function testSetAndGetTypeLocation(): void
    {
        $this->location->setTypeLocation('vehicule');
        $this->assertSame('vehicule', $this->location->getTypeLocation());

        $this->location->setTypeLocation('terrain');
        $this->assertSame('terrain', $this->location->getTypeLocation());
    }

    public function testSetAndGetVehicule(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setNom('Toyota Hilux');
        $this->location->setVehicule($vehicule);
        $this->assertSame($vehicule, $this->location->getVehicule());

        $this->location->setVehicule(null);
        $this->assertNull($this->location->getVehicule());
    }

    public function testSetAndGetTerrain(): void
    {
        $terrain = new Terrain();
        $terrain->setTitre('Terrain Sousse');
        $this->location->setTerrain($terrain);
        $this->assertSame($terrain, $this->location->getTerrain());

        $this->location->setTerrain(null);
        $this->assertNull($this->location->getTerrain());
    }

    public function testSetAndGetNumeroLocation(): void
    {
        $this->location->setNumeroLocation('LOC-2025-042');
        $this->assertSame('LOC-2025-042', $this->location->getNumeroLocation());
    }

    public function testSetAndGetDates(): void
    {
        $debut = new \DateTime('2025-04-01');
        $fin = new \DateTime('2025-04-15');

        $this->location->setDateDebut($debut);
        $this->location->setDateFin($fin);

        $this->assertSame($debut, $this->location->getDateDebut());
        $this->assertSame($fin, $this->location->getDateFin());
    }

    public function testSetAndGetDureeJours(): void
    {
        $this->location->setDureeJours(14);
        $this->assertSame(14, $this->location->getDureeJours());

        $this->location->setDureeJours(null);
        $this->assertNull($this->location->getDureeJours());
    }

    public function testSetAndGetPrixTotal(): void
    {
        $this->location->setPrixTotal('2100.00');
        $this->assertSame('2100.00', $this->location->getPrixTotal());
    }

    public function testSetAndGetCaution(): void
    {
        $this->location->setCaution('500.00');
        $this->assertSame('500.00', $this->location->getCaution());
    }

    public function testSetAndGetStatut(): void
    {
        $statuts = ['en_attente', 'confirmee', 'en_cours', 'terminee', 'annulee'];
        foreach ($statuts as $statut) {
            $this->location->setStatut($statut);
            $this->assertSame($statut, $this->location->getStatut());
        }
    }

    public function testSetAndGetDateReservation(): void
    {
        $date = new \DateTime('2025-03-28 10:00:00');
        $this->location->setDateReservation($date);
        $this->assertSame($date, $this->location->getDateReservation());
    }

    public function testSetAndGetNotes(): void
    {
        $this->location->setNotes('Besoin d\'une remorque');
        $this->assertSame('Besoin d\'une remorque', $this->location->getNotes());

        $this->location->setNotes(null);
        $this->assertNull($this->location->getNotes());
    }

    public function testGetItemNameVehicule(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setNom('Camion Renault');

        $this->location->setTypeLocation('vehicule');
        $this->location->setVehicule($vehicule);

        $this->assertSame('Camion Renault', $this->location->getItemName());
    }

    public function testGetItemNameTerrain(): void
    {
        $terrain = new Terrain();
        $terrain->setTitre('Terrain Bizerte');

        $this->location->setTypeLocation('terrain');
        $this->location->setTerrain($terrain);

        $this->assertSame('Terrain Bizerte', $this->location->getItemName());
    }

    public function testGetItemNameEmpty(): void
    {
        $this->location->setTypeLocation('vehicule');
        $this->assertSame('', $this->location->getItemName());

        $this->location->setTypeLocation('terrain');
        $this->assertSame('', $this->location->getItemName());
    }

    public function testGetItemNameWrongType(): void
    {
        $vehicule = new Vehicule();
        $vehicule->setNom('Test');
        $this->location->setTypeLocation('terrain');
        $this->location->setVehicule($vehicule);
        $this->assertSame('', $this->location->getItemName());
    }

    public function testFluentSetters(): void
    {
        $result = $this->location
            ->setTypeLocation('vehicule')
            ->setNumeroLocation('LOC-001')
            ->setPrixTotal('500.00')
            ->setStatut('confirmee');

        $this->assertSame($this->location, $result);
    }
}
