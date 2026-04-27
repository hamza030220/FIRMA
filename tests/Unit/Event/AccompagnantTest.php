<?php

namespace App\Tests\Unit\Event;

use App\Entity\Event\Accompagnant;
use App\Entity\Event\Participation;
use PHPUnit\Framework\TestCase;

class AccompagnantTest extends TestCase
{
    private Accompagnant $accompagnant;

    protected function setUp(): void
    {
        $this->accompagnant = new Accompagnant();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->accompagnant->getIdAccompagnant());
        $this->assertNull($this->accompagnant->getParticipation());
        $this->assertNull($this->accompagnant->getNom());
        $this->assertNull($this->accompagnant->getPrenom());
        $this->assertNull($this->accompagnant->getCodeAccompagnant());
    }

    public function testSetAndGetParticipation(): void
    {
        $participation = new Participation();
        $result = $this->accompagnant->setParticipation($participation);
        $this->assertSame($participation, $this->accompagnant->getParticipation());
        $this->assertSame($this->accompagnant, $result);
    }

    public function testSetAndGetNom(): void
    {
        $this->accompagnant->setNom('Ben Salah');
        $this->assertSame('Ben Salah', $this->accompagnant->getNom());
    }

    public function testSetAndGetPrenom(): void
    {
        $this->accompagnant->setPrenom('Mohamed');
        $this->assertSame('Mohamed', $this->accompagnant->getPrenom());
    }

    public function testSetAndGetCodeAccompagnant(): void
    {
        $this->accompagnant->setCodeAccompagnant('ACC-A1B2C');
        $this->assertSame('ACC-A1B2C', $this->accompagnant->getCodeAccompagnant());

        $this->accompagnant->setCodeAccompagnant(null);
        $this->assertNull($this->accompagnant->getCodeAccompagnant());
    }

    public function testGetFullNameReturnsPrenomEspaceNom(): void
    {
        $this->accompagnant->setPrenom('Mohamed');
        $this->accompagnant->setNom('Ben Salah');
        $this->assertSame('Mohamed Ben Salah', $this->accompagnant->getFullName());
    }

    public function testGenererCodeFormat(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $code = Accompagnant::genererCode();
            $this->assertSame(9, strlen($code), "Code doit faire 9 caractères : {$code}");
            $this->assertStringStartsWith('ACC-', $code);
            $this->assertMatchesRegularExpression('/^ACC-[A-Z0-9]{5}$/', $code);
        }
    }

    public function testGenererCodeProducesDifferentValues(): void
    {
        $codes = [];
        for ($i = 0; $i < 50; $i++) {
            $codes[] = Accompagnant::genererCode();
        }
        $this->assertGreaterThanOrEqual(45, count(array_unique($codes)));
    }
}
