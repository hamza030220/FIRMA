<?php

namespace App\Tests\Unit\Maladie;

use App\Entity\Maladie\Maladie;
use App\Entity\Maladie\SolutionTraitement;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class MaladieTest extends TestCase
{
    private Maladie $maladie;

    protected function setUp(): void
    {
        $this->maladie = new Maladie();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->maladie->getId());
        $this->assertNull($this->maladie->getNom());
        $this->assertNull($this->maladie->getNomScientifique());
        $this->assertNull($this->maladie->getDescription());
        $this->assertNull($this->maladie->getSymptomes());
        $this->assertNull($this->maladie->getImageUrl());
        $this->assertSame('moyen', $this->maladie->getNiveauGravite());
        $this->assertNull($this->maladie->getSaisonFrequente());
        $this->assertNull($this->maladie->getTempMin());
        $this->assertNull($this->maladie->getTempMax());
        $this->assertNull($this->maladie->getHumiditeMin());
        $this->assertNull($this->maladie->getCreatedBy());
        $this->assertNull($this->maladie->getUpdatedBy());
        $this->assertNull($this->maladie->getCreatedAt());
        $this->assertNull($this->maladie->getUpdatedAt());
        $this->assertCount(0, $this->maladie->getSolutionTraitements());
    }

    public function testSetAndGetNom(): void
    {
        $result = $this->maladie->setNom('Mildiou');

        $this->assertSame('Mildiou', $this->maladie->getNom());
        $this->assertSame($this->maladie, $result);
    }

    public function testSetAndGetNomScientifique(): void
    {
        $this->maladie->setNomScientifique('Phytophthora infestans');
        $this->assertSame('Phytophthora infestans', $this->maladie->getNomScientifique());

        $this->maladie->setNomScientifique(null);
        $this->assertNull($this->maladie->getNomScientifique());
    }

    public function testSetAndGetDescription(): void
    {
        $this->maladie->setDescription('Maladie cryptogamique touchant les cultures.');
        $this->assertSame('Maladie cryptogamique touchant les cultures.', $this->maladie->getDescription());

        $this->maladie->setDescription(null);
        $this->assertNull($this->maladie->getDescription());
    }

    public function testSetAndGetSymptomes(): void
    {
        $this->maladie->setSymptomes('Taches brunes sur les feuilles.');
        $this->assertSame('Taches brunes sur les feuilles.', $this->maladie->getSymptomes());

        $this->maladie->setSymptomes(null);
        $this->assertNull($this->maladie->getSymptomes());
    }

    public function testSetAndGetImageUrl(): void
    {
        $this->maladie->setImageUrl('uploads/maladies/mildiou.jpg');
        $this->assertSame('uploads/maladies/mildiou.jpg', $this->maladie->getImageUrl());

        $this->maladie->setImageUrl(null);
        $this->assertNull($this->maladie->getImageUrl());
    }

    public function testSetAndGetNiveauGravite(): void
    {
        foreach (['faible', 'moyen', 'eleve'] as $niveau) {
            $this->maladie->setNiveauGravite($niveau);
            $this->assertSame($niveau, $this->maladie->getNiveauGravite());
        }

        $this->maladie->setNiveauGravite(null);
        $this->assertNull($this->maladie->getNiveauGravite());
    }

    public function testSetAndGetSaisonFrequente(): void
    {
        $this->maladie->setSaisonFrequente('Printemps');
        $this->assertSame('Printemps', $this->maladie->getSaisonFrequente());

        $this->maladie->setSaisonFrequente(null);
        $this->assertNull($this->maladie->getSaisonFrequente());
    }

    public function testSetAndGetWeatherThresholds(): void
    {
        $this->maladie->setTempMin(12.5);
        $this->maladie->setTempMax(28.0);
        $this->maladie->setHumiditeMin(75);

        $this->assertSame(12.5, $this->maladie->getTempMin());
        $this->assertSame(28.0, $this->maladie->getTempMax());
        $this->assertSame(75, $this->maladie->getHumiditeMin());

        $this->maladie->setTempMin(null);
        $this->maladie->setTempMax(null);
        $this->maladie->setHumiditeMin(null);

        $this->assertNull($this->maladie->getTempMin());
        $this->assertNull($this->maladie->getTempMax());
        $this->assertNull($this->maladie->getHumiditeMin());
    }

    public function testSetAndGetCreatedBy(): void
    {
        $user = new Utilisateur();
        $this->maladie->assignCreatedBy($user);
        $this->assertSame($user, $this->maladie->getCreatedBy());

        $this->maladie->assignUpdatedBy($user);
        $this->assertSame($user, $this->maladie->getUpdatedBy());
    }

    public function testLifecycleCallbacksSetDates(): void
    {
        $this->maladie->setCreatedAtValue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->maladie->getCreatedAt());

        $this->maladie->setUpdatedAtValue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->maladie->getUpdatedAt());
    }

    public function testSolutionTraitementsCollection(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->maladie->getSolutionTraitements());

        $this->maladie->getSolutionTraitements()->add(new SolutionTraitement());
        $this->assertCount(1, $this->maladie->getSolutionTraitements());
    }
}
