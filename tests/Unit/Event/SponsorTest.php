<?php

namespace App\Tests\Unit\Event;

use App\Entity\Event\Evenement;
use App\Entity\Event\SecteurActivite;
use App\Entity\Event\Sponsor;
use PHPUnit\Framework\TestCase;

class SponsorTest extends TestCase
{
    private Sponsor $sponsor;

    protected function setUp(): void
    {
        $this->sponsor = new Sponsor();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->sponsor->getIdSponsor());
        $this->assertNull($this->sponsor->getNom());
        $this->assertNull($this->sponsor->getLogoUrl());
        $this->assertNull($this->sponsor->getSiteWeb());
        $this->assertNull($this->sponsor->getEmailContact());
        $this->assertNull($this->sponsor->getTelephone());
        $this->assertNull($this->sponsor->getDescription());
        $this->assertSame('0', $this->sponsor->getMontantContribution());
        $this->assertSame('autre', $this->sponsor->getSecteurActivite());
        $this->assertNull($this->sponsor->getEvenement());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->sponsor->getDateCreation());
    }

    public function testSetAndGetNom(): void
    {
        $this->sponsor->setNom('AgriCorp');
        $this->assertSame('AgriCorp', $this->sponsor->getNom());
    }

    public function testSetAndGetLogoUrl(): void
    {
        $this->sponsor->setLogoUrl('uploads/sponsors/agricorp.png');
        $this->assertSame('uploads/sponsors/agricorp.png', $this->sponsor->getLogoUrl());
    }

    public function testSetAndGetSiteWeb(): void
    {
        $this->sponsor->setSiteWeb('https://agricorp.tn');
        $this->assertSame('https://agricorp.tn', $this->sponsor->getSiteWeb());
    }

    public function testSetAndGetEmailContact(): void
    {
        $this->sponsor->setEmailContact('contact@agricorp.tn');
        $this->assertSame('contact@agricorp.tn', $this->sponsor->getEmailContact());
    }

    public function testSetAndGetTelephone(): void
    {
        $this->sponsor->setTelephone('+216 70 123 456');
        $this->assertSame('+216 70 123 456', $this->sponsor->getTelephone());
    }

    public function testSetAndGetDescription(): void
    {
        $this->sponsor->setDescription('Partenaire principal de l\'événement.');
        $this->assertSame('Partenaire principal de l\'événement.', $this->sponsor->getDescription());
    }

    public function testSetAndGetMontantContribution(): void
    {
        $this->sponsor->setMontantContribution('15000.50');
        $this->assertSame('15000.50', $this->sponsor->getMontantContribution());
    }

    public function testSetMontantContributionNullDefaultsToZero(): void
    {
        $this->sponsor->setMontantContribution(null);
        $this->assertSame('0', $this->sponsor->getMontantContribution());
    }

    public function testSetAndGetSecteurActivite(): void
    {
        foreach (['tech', 'finance', 'sante', 'education', 'industrie', 'autre'] as $s) {
            $this->sponsor->setSecteurActivite($s);
            $this->assertSame($s, $this->sponsor->getSecteurActivite());
        }
    }

    public function testGetSecteurEnum(): void
    {
        $this->sponsor->setSecteurActivite('tech');
        $this->assertSame(SecteurActivite::TECH, $this->sponsor->getSecteurEnum());
    }

    public function testSetAndGetEvenement(): void
    {
        $event = new Evenement();
        $this->sponsor->setEvenement($event);
        $this->assertSame($event, $this->sponsor->getEvenement());
    }

    public function testIsCatalogTrueWhenNoEvenement(): void
    {
        $this->assertTrue($this->sponsor->isCatalog());
    }

    public function testIsCatalogFalseWhenAssignedToEvenement(): void
    {
        $this->sponsor->setEvenement(new Evenement());
        $this->assertFalse($this->sponsor->isCatalog());
    }

    public function testCloneForEventCopiesFieldsAndAssignsEvenement(): void
    {
        $this->sponsor
            ->setNom('AgriCorp')
            ->setLogoUrl('logo.png')
            ->setSiteWeb('https://agricorp.tn')
            ->setEmailContact('contact@agricorp.tn')
            ->setTelephone('+216 70 123 456')
            ->setDescription('Partenaire')
            ->setSecteurActivite('tech')
            ->setMontantContribution('1000.00');

        $event = new Evenement();
        $clone = $this->sponsor->cloneForEvent($event);

        $this->assertNotSame($this->sponsor, $clone);
        $this->assertSame('AgriCorp', $clone->getNom());
        $this->assertSame('logo.png', $clone->getLogoUrl());
        $this->assertSame('https://agricorp.tn', $clone->getSiteWeb());
        $this->assertSame('contact@agricorp.tn', $clone->getEmailContact());
        $this->assertSame('+216 70 123 456', $clone->getTelephone());
        $this->assertSame('Partenaire', $clone->getDescription());
        $this->assertSame('tech', $clone->getSecteurActivite());
        $this->assertSame('1000.00', $clone->getMontantContribution());
        $this->assertSame($event, $clone->getEvenement());
        $this->assertFalse($clone->isCatalog());
    }

    public function testCloneForEventOverridesMontantWhenProvided(): void
    {
        $this->sponsor->setMontantContribution('1000.00');
        $clone = $this->sponsor->cloneForEvent(new Evenement(), '5000.00');

        $this->assertSame('5000.00', $clone->getMontantContribution());
    }
}
