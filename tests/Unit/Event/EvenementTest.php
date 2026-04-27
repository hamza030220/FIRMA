<?php

namespace App\Tests\Unit\Event;

use App\Entity\Event\Evenement;
use App\Entity\Event\Participation;
use App\Entity\Event\Sponsor;
use App\Entity\Event\StatutEvenement;
use App\Entity\Event\TypeEvenement;
use PHPUnit\Framework\TestCase;

class EvenementTest extends TestCase
{
    private Evenement $evenement;

    protected function setUp(): void
    {
        $this->evenement = new Evenement();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->evenement->getIdEvenement());
        $this->assertNull($this->evenement->getTitre());
        $this->assertNull($this->evenement->getDescription());
        $this->assertNull($this->evenement->getImageUrl());
        $this->assertNull($this->evenement->getTypeEvenement());
        $this->assertNull($this->evenement->getDateDebut());
        $this->assertNull($this->evenement->getDateFin());
        $this->assertNull($this->evenement->getLieu());
        $this->assertNull($this->evenement->getAdresse());
        $this->assertNull($this->evenement->getCapaciteMax());
        $this->assertNull($this->evenement->getPlacesDisponibles());
        $this->assertNull($this->evenement->getOrganisateur());
        $this->assertSame('actif', $this->evenement->getStatut());
        $this->assertCount(0, $this->evenement->getParticipations());
        $this->assertCount(0, $this->evenement->getSponsors());
    }

    public function testSetAndGetTitre(): void
    {
        $result = $this->evenement->setTitre('Salon Agriculture 2026');
        $this->assertSame('Salon Agriculture 2026', $this->evenement->getTitre());
        $this->assertSame($this->evenement, $result);
    }

    public function testSetAndGetDescription(): void
    {
        $this->evenement->setDescription('Un grand salon dédié à l\'agriculture moderne.');
        $this->assertSame('Un grand salon dédié à l\'agriculture moderne.', $this->evenement->getDescription());
    }

    public function testSetAndGetImageUrl(): void
    {
        $this->evenement->setImageUrl('uploads/events/salon-2026.jpg');
        $this->assertSame('uploads/events/salon-2026.jpg', $this->evenement->getImageUrl());
    }

    public function testSetAndGetTypeEvenement(): void
    {
        foreach (['exposition', 'atelier', 'conference', 'salon', 'formation', 'autre'] as $type) {
            $this->evenement->setTypeEvenement($type);
            $this->assertSame($type, $this->evenement->getTypeEvenement());
        }
    }

    public function testGetTypeEnumReturnsEnum(): void
    {
        $this->evenement->setTypeEvenement('salon');
        $this->assertSame(TypeEvenement::SALON, $this->evenement->getTypeEnum());
    }

    public function testGetTypeEnumReturnsNullForInvalid(): void
    {
        $this->evenement->setTypeEvenement('inexistant');
        $this->assertNull($this->evenement->getTypeEnum());
    }

    public function testSetAndGetDateDebut(): void
    {
        $date = new \DateTime('2026-06-15 09:00:00');
        $this->evenement->setDateDebut($date);
        $this->assertSame($date, $this->evenement->getDateDebut());
    }

    public function testSetAndGetDateFin(): void
    {
        $date = new \DateTime('2026-06-17 18:00:00');
        $this->evenement->setDateFin($date);
        $this->assertSame($date, $this->evenement->getDateFin());
    }

    public function testSetAndGetHoraireDebut(): void
    {
        $h = new \DateTime('09:00:00');
        $this->evenement->setHoraireDebut($h);
        $this->assertSame($h, $this->evenement->getHoraireDebut());
    }

    public function testSetAndGetHoraireFin(): void
    {
        $h = new \DateTime('18:00:00');
        $this->evenement->setHoraireFin($h);
        $this->assertSame($h, $this->evenement->getHoraireFin());
    }

    public function testSetAndGetLieu(): void
    {
        $this->evenement->setLieu('Parc des Expositions');
        $this->assertSame('Parc des Expositions', $this->evenement->getLieu());
    }

    public function testSetAndGetAdresse(): void
    {
        $this->evenement->setAdresse('Avenue Habib Bourguiba, Tunis');
        $this->assertSame('Avenue Habib Bourguiba, Tunis', $this->evenement->getAdresse());
    }

    public function testSetAndGetCapaciteMax(): void
    {
        $this->evenement->setCapaciteMax(500);
        $this->assertSame(500, $this->evenement->getCapaciteMax());
    }

    public function testSetAndGetPlacesDisponibles(): void
    {
        $this->evenement->setPlacesDisponibles(125);
        $this->assertSame(125, $this->evenement->getPlacesDisponibles());
    }

    public function testSetAndGetOrganisateur(): void
    {
        $this->evenement->setOrganisateur('FIRMA');
        $this->assertSame('FIRMA', $this->evenement->getOrganisateur());
    }

    public function testSetAndGetContactEmail(): void
    {
        $this->evenement->setContactEmail('contact@firma.tn');
        $this->assertSame('contact@firma.tn', $this->evenement->getContactEmail());
    }

    public function testSetAndGetContactTel(): void
    {
        $this->evenement->setContactTel('+216 70 123 456');
        $this->assertSame('+216 70 123 456', $this->evenement->getContactTel());
    }

    public function testSetAndGetStatut(): void
    {
        foreach (['actif', 'annule', 'termine', 'complet'] as $statut) {
            $this->evenement->setStatut($statut);
            $this->assertSame($statut, $this->evenement->getStatut());
        }
    }

    public function testGetStatutEnum(): void
    {
        $this->evenement->setStatut('annule');
        $this->assertSame(StatutEvenement::ANNULE, $this->evenement->getStatutEnum());
    }

    public function testIsModifiableTrueWhenActif(): void
    {
        $this->evenement->setStatut('actif');
        $this->assertTrue($this->evenement->isModifiable());
    }

    public function testIsModifiableTrueWhenComplet(): void
    {
        $this->evenement->setStatut('complet');
        $this->assertTrue($this->evenement->isModifiable());
    }

    public function testIsModifiableFalseWhenAnnule(): void
    {
        $this->evenement->setStatut('annule');
        $this->assertFalse($this->evenement->isModifiable());
    }

    public function testIsModifiableFalseWhenTermine(): void
    {
        $this->evenement->setStatut('termine');
        $this->assertFalse($this->evenement->isModifiable());
    }

    public function testGetGoogleMapsUrlBuildsUrlWhenLieuAndAdresse(): void
    {
        $this->evenement->setLieu('Parc des Expositions');
        $this->evenement->setAdresse('Tunis');
        $url = $this->evenement->getGoogleMapsUrl();
        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://www.google.com/maps/search/?api=1&query=', $url);
        $this->assertStringContainsString('Parc', $url);
        $this->assertStringContainsString('Tunis', $url);
    }

    public function testGetGoogleMapsUrlReturnsNullWhenEmpty(): void
    {
        $this->assertNull($this->evenement->getGoogleMapsUrl());
    }

    public function testOnPrePersistInitialisesDates(): void
    {
        $this->evenement->setCapaciteMax(100);
        $this->evenement->onPrePersist();

        $this->assertInstanceOf(\DateTimeInterface::class, $this->evenement->getDateCreation());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->evenement->getDateModification());
        $this->assertSame(100, $this->evenement->getPlacesDisponibles());
        $this->assertSame('actif', $this->evenement->getStatut());
    }

    public function testOnPrePersistDoesNotOverridePlacesIfAlreadySet(): void
    {
        $this->evenement->setCapaciteMax(100);
        $this->evenement->setPlacesDisponibles(42);
        $this->evenement->onPrePersist();

        $this->assertSame(42, $this->evenement->getPlacesDisponibles());
    }

    public function testOnPreUpdateRefreshesDateModification(): void
    {
        $old = new \DateTime('2024-01-01');
        $this->evenement->setDateModification($old);
        $this->evenement->onPreUpdate();

        $this->assertNotSame($old, $this->evenement->getDateModification());
        $this->assertGreaterThanOrEqual($old, $this->evenement->getDateModification());
    }

    public function testParticipationsCollectionIsInitialized(): void
    {
        $this->assertCount(0, $this->evenement->getParticipations());
    }

    public function testSponsorsCollectionIsInitialized(): void
    {
        $this->assertCount(0, $this->evenement->getSponsors());
    }

    public function testParticipationsCollectionAcceptsParticipation(): void
    {
        $this->evenement->getParticipations()->add(new Participation());
        $this->assertCount(1, $this->evenement->getParticipations());
    }

    public function testSponsorsCollectionAcceptsSponsor(): void
    {
        $this->evenement->getSponsors()->add(new Sponsor());
        $this->assertCount(1, $this->evenement->getSponsors());
    }
}
