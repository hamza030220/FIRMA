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

    // ── Edge cases — titre ────────────────────────────────────────────────────

    public function testSetTitreToNull(): void
    {
        $this->evenement->setTitre('Salon');
        $this->evenement->setTitre(null);
        $this->assertNull($this->evenement->getTitre());
    }

    public function testSetTitreWithEmptyString(): void
    {
        // PHP level accepts it; Symfony validator would reject it
        $this->evenement->setTitre('');
        $this->assertSame('', $this->evenement->getTitre());
    }

    public function testSetTitreFluentInterface(): void
    {
        $result = $this->evenement->setTitre('Test');
        $this->assertSame($this->evenement, $result);
    }

    // ── Edge cases — capacité ─────────────────────────────────────────────────

    public function testSetCapaciteMaxToZero(): void
    {
        // Zero is stored at PHP level; Symfony Range validator (min:1) would reject it
        $this->evenement->setCapaciteMax(0);
        $this->assertSame(0, $this->evenement->getCapaciteMax());
    }

    public function testSetCapaciteMaxToNegative(): void
    {
        // Negative stored at PHP level; validator would reject in real usage
        $this->evenement->setCapaciteMax(-10);
        $this->assertSame(-10, $this->evenement->getCapaciteMax());
    }

    public function testSetPlacesDisponiblesZero(): void
    {
        $this->evenement->setPlacesDisponibles(0);
        $this->assertSame(0, $this->evenement->getPlacesDisponibles());
    }

    public function testSetPlacesDisponiblesNegative(): void
    {
        // Can happen if more people are registered than capacity (over-booking edge case)
        $this->evenement->setPlacesDisponibles(-5);
        $this->assertSame(-5, $this->evenement->getPlacesDisponibles());
    }

    // ── Edge cases — statut ───────────────────────────────────────────────────

    public function testGetStatutEnumReturnsNullForUnknownStatut(): void
    {
        $this->evenement->setStatut('inconnu');
        $this->assertNull($this->evenement->getStatutEnum());
    }

    public function testIsModifiableWithUnknownStatut(): void
    {
        // Unknown statut is not in ['annule','termine'], so isModifiable returns true
        $this->evenement->setStatut('inconnu');
        $this->assertTrue($this->evenement->isModifiable());
    }

    // ── Edge cases — getTypeEnum ──────────────────────────────────────────────

    public function testGetTypeEnumIsCaseSensitive(): void
    {
        $this->evenement->setTypeEvenement('SALON'); // uppercase — not a valid enum value
        $this->assertNull($this->evenement->getTypeEnum());
    }

    // ── Edge cases — dates ────────────────────────────────────────────────────

    public function testDateDebutCanBeAfterDateFin(): void
    {
        // Entity does not enforce chronological order — that is a form/validator concern
        $debut = new \DateTime('2026-12-31');
        $fin   = new \DateTime('2026-01-01');
        $this->evenement->setDateDebut($debut);
        $this->evenement->setDateFin($fin);
        $this->assertSame($debut, $this->evenement->getDateDebut());
        $this->assertSame($fin, $this->evenement->getDateFin());
    }

    public function testSameDateDebutAndDateFin(): void
    {
        $date = new \DateTime('2026-06-15');
        $this->evenement->setDateDebut($date);
        $this->evenement->setDateFin($date);
        $this->assertSame($this->evenement->getDateDebut(), $this->evenement->getDateFin());
    }

    // ── Edge cases — getGoogleMapsUrl ─────────────────────────────────────────

    public function testGetGoogleMapsUrlWithOnlyLieu(): void
    {
        $this->evenement->setLieu('Sfax');
        $url = $this->evenement->getGoogleMapsUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString('Sfax', $url);
    }

    public function testGetGoogleMapsUrlWithOnlyAdresse(): void
    {
        $this->evenement->setAdresse('Rue de la Liberté, Sousse');
        $url = $this->evenement->getGoogleMapsUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString('Sousse', $url);
    }

    public function testGetGoogleMapsUrlIsUrlEncoded(): void
    {
        $this->evenement->setLieu('Parc des Expositions');
        $this->evenement->setAdresse('Tunis');
        $url = $this->evenement->getGoogleMapsUrl();
        // spaces must be encoded — no raw space in the query string
        $this->assertStringNotContainsString(' ', $url);
    }

    // ── Edge cases — onPrePersist ─────────────────────────────────────────────

    public function testOnPrePersistDoesNotOverrideDateCreationIfAlreadySet(): void
    {
        $existing = new \DateTime('2020-01-01');
        $this->evenement->setDateCreation($existing);
        $this->evenement->setCapaciteMax(10);
        $this->evenement->onPrePersist();
        $this->assertSame($existing, $this->evenement->getDateCreation());
    }

    public function testOnPrePersistWithNullCapaciteMaxLeavesPlacesDisponiblesNull(): void
    {
        // capaciteMax is null → placesDisponibles stays null (??= null = null)
        $this->evenement->onPrePersist();
        $this->assertNull($this->evenement->getPlacesDisponibles());
    }

    // ── Edge cases — contact ──────────────────────────────────────────────────

    public function testSetContactEmailToNull(): void
    {
        $this->evenement->setContactEmail('x@y.com');
        $this->evenement->setContactEmail(null);
        $this->assertNull($this->evenement->getContactEmail());
    }

    public function testSetContactTelToNull(): void
    {
        $this->evenement->setContactTel('+216 70 000 000');
        $this->evenement->setContactTel(null);
        $this->assertNull($this->evenement->getContactTel());
    }

    // ── Edge cases — lieu / adresse ───────────────────────────────────────────

    public function testSetLieuToNull(): void
    {
        $this->evenement->setLieu('Tunis');
        $this->evenement->setLieu(null);
        $this->assertNull($this->evenement->getLieu());
    }

    public function testSetAdresseToNull(): void
    {
        $this->evenement->setAdresse('Rue X');
        $this->evenement->setAdresse(null);
        $this->assertNull($this->evenement->getAdresse());
    }
}
