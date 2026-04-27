<?php

namespace App\Tests\Unit\Event;

use App\Entity\Event\TypeEvenement;
use PHPUnit\Framework\TestCase;

class TypeEvenementTest extends TestCase
{
    public function testEnumCases(): void
    {
        $this->assertSame('exposition', TypeEvenement::EXPOSITION->value);
        $this->assertSame('atelier',    TypeEvenement::ATELIER->value);
        $this->assertSame('conference', TypeEvenement::CONFERENCE->value);
        $this->assertSame('salon',      TypeEvenement::SALON->value);
        $this->assertSame('formation',  TypeEvenement::FORMATION->value);
        $this->assertSame('autre',      TypeEvenement::AUTRE->value);
    }

    public function testTryFromValidValues(): void
    {
        foreach (['exposition', 'atelier', 'conference', 'salon', 'formation', 'autre'] as $value) {
            $this->assertNotNull(TypeEvenement::tryFrom($value));
            $this->assertSame($value, TypeEvenement::tryFrom($value)?->value);
        }
    }

    public function testTryFromInvalidReturnsNull(): void
    {
        $this->assertNull(TypeEvenement::tryFrom('inexistant'));
    }

    public function testLabel(): void
    {
        $this->assertSame('Exposition', TypeEvenement::EXPOSITION->label());
        $this->assertSame('Atelier',    TypeEvenement::ATELIER->label());
        $this->assertSame('Conférence', TypeEvenement::CONFERENCE->label());
        $this->assertSame('Salon',      TypeEvenement::SALON->label());
        $this->assertSame('Formation',  TypeEvenement::FORMATION->label());
        $this->assertSame('Autre',      TypeEvenement::AUTRE->label());
    }

    public function testCasesCount(): void
    {
        $this->assertCount(6, TypeEvenement::cases());
    }
}
