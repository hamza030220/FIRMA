<?php

namespace App\Tests\Unit\Event;

use App\Entity\Event\StatutEvenement;
use PHPUnit\Framework\TestCase;

class StatutEvenementTest extends TestCase
{
    public function testEnumCases(): void
    {
        $this->assertSame('actif',   StatutEvenement::ACTIF->value);
        $this->assertSame('annule',  StatutEvenement::ANNULE->value);
        $this->assertSame('termine', StatutEvenement::TERMINE->value);
        $this->assertSame('complet', StatutEvenement::COMPLET->value);
    }

    public function testTryFromValidValues(): void
    {
        $this->assertSame(StatutEvenement::ACTIF,   StatutEvenement::tryFrom('actif'));
        $this->assertSame(StatutEvenement::ANNULE,  StatutEvenement::tryFrom('annule'));
        $this->assertSame(StatutEvenement::TERMINE, StatutEvenement::tryFrom('termine'));
        $this->assertSame(StatutEvenement::COMPLET, StatutEvenement::tryFrom('complet'));
    }

    public function testTryFromInvalidReturnsNull(): void
    {
        $this->assertNull(StatutEvenement::tryFrom('inexistant'));
    }

    public function testLabel(): void
    {
        $this->assertSame('Actif',    StatutEvenement::ACTIF->label());
        $this->assertSame('Annulé',   StatutEvenement::ANNULE->label());
        $this->assertSame('Terminé',  StatutEvenement::TERMINE->label());
        $this->assertSame('Complet',  StatutEvenement::COMPLET->label());
    }

    public function testBadgeClass(): void
    {
        $this->assertSame('badge-success',   StatutEvenement::ACTIF->badgeClass());
        $this->assertSame('badge-danger',    StatutEvenement::ANNULE->badgeClass());
        $this->assertSame('badge-secondary', StatutEvenement::TERMINE->badgeClass());
        $this->assertSame('badge-warning',   StatutEvenement::COMPLET->badgeClass());
    }
}
