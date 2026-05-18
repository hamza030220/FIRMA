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

    // ── Initial state ──────────────────────────────────────────────────────────

    public function testInitialState(): void
    {
        $this->assertNull($this->accompagnant->getIdAccompagnant());
        $this->assertNull($this->accompagnant->getParticipation());
        $this->assertNull($this->accompagnant->getNom());
        $this->assertNull($this->accompagnant->getPrenom());
        $this->assertNull($this->accompagnant->getCodeAccompagnant());
    }

    // ── Participation ──────────────────────────────────────────────────────────

    public function testSetAndGetParticipation(): void
    {
        $participation = new Participation();
        $result = $this->accompagnant->setParticipation($participation);
        $this->assertSame($participation, $this->accompagnant->getParticipation());
        $this->assertSame($this->accompagnant, $result); // fluent interface
    }

    public function testSetParticipationToNull(): void
    {
        $this->accompagnant->setParticipation(new Participation());
        $this->accompagnant->setParticipation(null);
        $this->assertNull($this->accompagnant->getParticipation());
    }

    public function testReplaceParticipationWithAnother(): void
    {
        $p1 = new Participation();
        $p2 = new Participation();
        $this->accompagnant->setParticipation($p1);
        $this->accompagnant->setParticipation($p2);
        $this->assertSame($p2, $this->accompagnant->getParticipation());
    }

    // ── Nom ───────────────────────────────────────────────────────────────────

    public function testSetAndGetNom(): void
    {
        $this->accompagnant->setNom('Ben Salah');
        $this->assertSame('Ben Salah', $this->accompagnant->getNom());
    }

    public function testNomWithAccentedCharacters(): void
    {
        $this->accompagnant->setNom('Élodie Müller-García');
        $this->assertSame('Élodie Müller-García', $this->accompagnant->getNom());
    }

    public function testNomWithMaxLength(): void
    {
        $long = str_repeat('A', 255);
        $this->accompagnant->setNom($long);
        $this->assertSame($long, $this->accompagnant->getNom());
        $this->assertSame(255, strlen($this->accompagnant->getNom()));
    }

    public function testNomWithEmptyString(): void
    {
        // PHP level accepts it — validation is handled by Symfony constraints
        $this->accompagnant->setNom('');
        $this->assertSame('', $this->accompagnant->getNom());
    }

    // ── Prénom ────────────────────────────────────────────────────────────────

    public function testSetAndGetPrenom(): void
    {
        $this->accompagnant->setPrenom('Mohamed');
        $this->assertSame('Mohamed', $this->accompagnant->getPrenom());
    }

    public function testPrenomWithHyphen(): void
    {
        $this->accompagnant->setPrenom('Jean-Pierre');
        $this->assertSame('Jean-Pierre', $this->accompagnant->getPrenom());
    }

    public function testPrenomWithMaxLength(): void
    {
        $long = str_repeat('B', 255);
        $this->accompagnant->setPrenom($long);
        $this->assertSame(255, strlen($this->accompagnant->getPrenom()));
    }

    // ── Code accompagnant ──────────────────────────────────────────────────────

    public function testSetAndGetCodeAccompagnant(): void
    {
        $this->accompagnant->setCodeAccompagnant('ACC-A1B2C');
        $this->assertSame('ACC-A1B2C', $this->accompagnant->getCodeAccompagnant());
    }

    public function testSetCodeAccompagnantToNull(): void
    {
        $this->accompagnant->setCodeAccompagnant('ACC-XXXXX');
        $this->accompagnant->setCodeAccompagnant(null);
        $this->assertNull($this->accompagnant->getCodeAccompagnant());
    }

    // ── getFullName ───────────────────────────────────────────────────────────

    public function testGetFullNameReturnsPrenomEspaceNom(): void
    {
        $this->accompagnant->setPrenom('Mohamed');
        $this->accompagnant->setNom('Ben Salah');
        $this->assertSame('Mohamed Ben Salah', $this->accompagnant->getFullName());
    }

    public function testGetFullNameWithSingleWordNames(): void
    {
        $this->accompagnant->setPrenom('Ali');
        $this->accompagnant->setNom('Karray');
        $this->assertSame('Ali Karray', $this->accompagnant->getFullName());
    }

    public function testGetFullNameWithAccentedNames(): void
    {
        $this->accompagnant->setPrenom('Léa');
        $this->accompagnant->setNom('Dupré');
        $this->assertSame('Léa Dupré', $this->accompagnant->getFullName());
    }

    public function testGetFullNameContainsSpaceSeparator(): void
    {
        $this->accompagnant->setPrenom('X');
        $this->accompagnant->setNom('Y');
        $this->assertStringContainsString(' ', $this->accompagnant->getFullName());
    }

    // ── genererCode ───────────────────────────────────────────────────────────

    public function testGenererCodeFormat(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $code = Accompagnant::genererCode();
            $this->assertSame(9, strlen($code), "Code doit faire 9 caractères : {$code}");
            $this->assertStringStartsWith('ACC-', $code);
            $this->assertMatchesRegularExpression('/^ACC-[A-Z0-9]{5}$/', $code);
        }
    }

    public function testGenererCodeContainsOnlyUppercaseAlphanumeric(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $suffix = substr(Accompagnant::genererCode(), 4); // strip "ACC-"
            $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $suffix);
            $this->assertDoesNotMatchRegularExpression('/[a-z]/', $suffix, 'Code must not contain lowercase letters');
        }
    }

    public function testGenererCodeNeverContainsSpecialCharacters(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $code = Accompagnant::genererCode();
            $this->assertDoesNotMatchRegularExpression('/[^A-Z0-9\-]/', $code);
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
