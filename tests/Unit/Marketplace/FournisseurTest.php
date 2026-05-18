<?php

namespace App\Tests\Unit\Marketplace;

use App\Entity\Marketplace\Equipement;
use App\Entity\Marketplace\Fournisseur;
use PHPUnit\Framework\TestCase;

class FournisseurTest extends TestCase
{
    private Fournisseur $fournisseur;

    protected function setUp(): void
    {
        $this->fournisseur = new Fournisseur();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->fournisseur->getId());
        $this->assertNull($this->fournisseur->getNomEntreprise());
        $this->assertNull($this->fournisseur->getContactNom());
        $this->assertNull($this->fournisseur->getEmail());
        $this->assertNull($this->fournisseur->getTelephone());
        $this->assertNull($this->fournisseur->getAdresse());
        $this->assertNull($this->fournisseur->getVille());
        $this->assertTrue($this->fournisseur->isActif());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->fournisseur->getDateCreation());
        $this->assertCount(0, $this->fournisseur->getEquipements());
    }

    public function testSetAndGetNomEntreprise(): void
    {
        $result = $this->fournisseur->setNomEntreprise('Agri-Pro SARL');
        $this->assertSame('Agri-Pro SARL', $this->fournisseur->getNomEntreprise());
        $this->assertSame($this->fournisseur, $result);
    }

    public function testSetAndGetContactNom(): void
    {
        $this->fournisseur->setContactNom('Ahmed Ben Ali');
        $this->assertSame('Ahmed Ben Ali', $this->fournisseur->getContactNom());
    }

    public function testSetAndGetEmail(): void
    {
        $this->fournisseur->setEmail('contact@agripro.tn');
        $this->assertSame('contact@agripro.tn', $this->fournisseur->getEmail());
    }

    public function testSetAndGetTelephone(): void
    {
        $this->fournisseur->setTelephone('+216 71 123 456');
        $this->assertSame('+216 71 123 456', $this->fournisseur->getTelephone());
    }

    public function testSetAndGetAdresse(): void
    {
        $this->fournisseur->setAdresse('Zone industrielle, Rue 5');
        $this->assertSame('Zone industrielle, Rue 5', $this->fournisseur->getAdresse());
    }

    public function testSetAndGetVille(): void
    {
        $this->fournisseur->setVille('Tunis');
        $this->assertSame('Tunis', $this->fournisseur->getVille());
    }

    public function testSetAndGetActif(): void
    {
        $this->fournisseur->setActif(false);
        $this->assertFalse($this->fournisseur->isActif());

        $this->fournisseur->setActif(true);
        $this->assertTrue($this->fournisseur->isActif());
    }

    public function testSetAndGetDateCreation(): void
    {
        $date = new \DateTime('2025-01-15');
        $this->fournisseur->setDateCreation($date);
        $this->assertSame($date, $this->fournisseur->getDateCreation());
    }

    public function testToString(): void
    {
        $this->assertSame('', (string) $this->fournisseur);
        $this->fournisseur->setNomEntreprise('TechnoFarm');
        $this->assertSame('TechnoFarm', (string) $this->fournisseur);
    }

    public function testNullableFields(): void
    {
        $this->fournisseur->setContactNom(null);
        $this->assertNull($this->fournisseur->getContactNom());

        $this->fournisseur->setEmail(null);
        $this->assertNull($this->fournisseur->getEmail());

        $this->fournisseur->setTelephone(null);
        $this->assertNull($this->fournisseur->getTelephone());

        $this->fournisseur->setAdresse(null);
        $this->assertNull($this->fournisseur->getAdresse());

        $this->fournisseur->setVille(null);
        $this->assertNull($this->fournisseur->getVille());
    }

    // ── Edge cases — actif ────────────────────────────────────────────────────

    public function testActifToggleFalseToTrue(): void
    {
        $this->fournisseur->setActif(false);
        $this->assertFalse($this->fournisseur->isActif());
        $this->fournisseur->setActif(true);
        $this->assertTrue($this->fournisseur->isActif());
    }

    public function testSetActifFluentInterface(): void
    {
        $result = $this->fournisseur->setActif(false);
        $this->assertSame($this->fournisseur, $result);
    }

    // ── Edge cases — nomEntreprise ────────────────────────────────────────────

    public function testSetNomEntrepriseWithAccentedChars(): void
    {
        $this->fournisseur->setNomEntreprise('Société Générale & Frères');
        $this->assertSame('Société Générale & Frères', $this->fournisseur->getNomEntreprise());
    }

    public function testSetNomEntrepriseWithMaxLength(): void
    {
        $long = str_repeat('A', 200);
        $this->fournisseur->setNomEntreprise($long);
        $this->assertSame(200, strlen($this->fournisseur->getNomEntreprise()));
    }

    public function testSetNomEntrepriseFluentInterface(): void
    {
        $result = $this->fournisseur->setNomEntreprise('Test');
        $this->assertSame($this->fournisseur, $result);
    }

    // ── Edge cases — email ────────────────────────────────────────────────────

    public function testSetEmailWithAnyString(): void
    {
        // Entity stores any string; validator handles format validation
        $this->fournisseur->setEmail('not-an-email');
        $this->assertSame('not-an-email', $this->fournisseur->getEmail());
    }

    // ── Edge cases — dateCreation ─────────────────────────────────────────────

    public function testDateCreationIsSetInConstructor(): void
    {
        $fresh = new Fournisseur();
        $this->assertInstanceOf(\DateTimeInterface::class, $fresh->getDateCreation());
    }

    // ── Edge cases — equipements collection ───────────────────────────────────

    public function testEquipementsCollectionInitiallyEmpty(): void
    {
        $this->assertCount(0, $this->fournisseur->getEquipements());
    }

    public function testEachFournisseurHasIndependentCollection(): void
    {
        $f2 = new Fournisseur();
        $f2->getEquipements()->add(new Equipement());

        $this->assertCount(0, $this->fournisseur->getEquipements());
        $this->assertCount(1, $f2->getEquipements());
    }
}
