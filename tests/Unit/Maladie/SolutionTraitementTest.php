<?php

namespace App\Tests\Unit\Maladie;

use App\Entity\Maladie\Maladie;
use App\Entity\Maladie\SolutionTraitement;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class SolutionTraitementTest extends TestCase
{
    private SolutionTraitement $solution;

    protected function setUp(): void
    {
        $this->solution = new SolutionTraitement();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->solution->getId());
        $this->assertNull($this->solution->getMaladie());
        $this->assertNull($this->solution->getTitre());
        $this->assertNull($this->solution->getSolution());
        $this->assertNull($this->solution->getEtapes());
        $this->assertNull($this->solution->getProduitsRecommandes());
        $this->assertNull($this->solution->getConseilsPrevention());
        $this->assertNull($this->solution->getDureeTraitement());
        $this->assertSame(0, $this->solution->getUsageCount());
        $this->assertSame(0, $this->solution->getFeedbackPositive());
        $this->assertSame(0, $this->solution->getFeedbackNegative());
        $this->assertNull($this->solution->getLastUsedAt());
        $this->assertNull($this->solution->getLastUserId());
        $this->assertNull($this->solution->getCreatedBy());
        $this->assertNull($this->solution->getUpdatedBy());
        $this->assertNull($this->solution->getCreatedAt());
        $this->assertNull($this->solution->getUpdatedAt());
    }

    public function testSetAndGetMaladie(): void
    {
        $maladie = new Maladie();
        $result = $this->solution->setMaladie($maladie);

        $this->assertSame($maladie, $this->solution->getMaladie());
        $this->assertSame($this->solution, $result);

        $this->solution->setMaladie(null);
        $this->assertNull($this->solution->getMaladie());
    }

    public function testSetAndGetTitre(): void
    {
        $result = $this->solution->setTitre('Traitement bio');

        $this->assertSame('Traitement bio', $this->solution->getTitre());
        $this->assertSame($this->solution, $result);
    }

    public function testSetAndGetSolution(): void
    {
        $this->solution->setSolution('Appliquer une solution naturelle sur les feuilles.');

        $this->assertSame('Appliquer une solution naturelle sur les feuilles.', $this->solution->getSolution());
    }

    public function testNullableTextFields(): void
    {
        $this->solution->setEtapes('Etape 1 puis etape 2');
        $this->solution->setProduitsRecommandes('Cuivre');
        $this->solution->setConseilsPrevention('Aeration des cultures');
        $this->solution->setDureeTraitement('7 jours');

        $this->assertSame('Etape 1 puis etape 2', $this->solution->getEtapes());
        $this->assertSame('Cuivre', $this->solution->getProduitsRecommandes());
        $this->assertSame('Aeration des cultures', $this->solution->getConseilsPrevention());
        $this->assertSame('7 jours', $this->solution->getDureeTraitement());

        $this->solution->setEtapes(null);
        $this->solution->setProduitsRecommandes(null);
        $this->solution->setConseilsPrevention(null);
        $this->solution->setDureeTraitement(null);

        $this->assertNull($this->solution->getEtapes());
        $this->assertNull($this->solution->getProduitsRecommandes());
        $this->assertNull($this->solution->getConseilsPrevention());
        $this->assertNull($this->solution->getDureeTraitement());
    }

    public function testCountersAndUserTracking(): void
    {
        $user = new Utilisateur();
        $this->solution->setUsageCount(10);
        $this->solution->setFeedbackPositive(7);
        $this->solution->setFeedbackNegative(3);
        $this->solution->setLastUserId(4);
        $this->solution->assignCreatedBy($user);
        $this->solution->assignUpdatedBy($user);

        $this->assertSame(10, $this->solution->getUsageCount());
        $this->assertSame(7, $this->solution->getFeedbackPositive());
        $this->assertSame(3, $this->solution->getFeedbackNegative());
        $this->assertSame(4, $this->solution->getLastUserId());
        $this->assertSame($user, $this->solution->getCreatedBy());
        $this->assertSame($user, $this->solution->getUpdatedBy());

        $this->solution->setLastUserId(null);

        $this->assertNull($this->solution->getLastUserId());
        $this->assertSame($user, $this->solution->getCreatedBy());
        $this->assertSame($user, $this->solution->getUpdatedBy());
    }

    public function testSetAndGetLastUsedAt(): void
    {
        $date = new \DateTimeImmutable('2026-04-26 10:00:00');
        $this->solution->setLastUsedAt($date);
        $this->assertSame($date, $this->solution->getLastUsedAt());

        $this->solution->setLastUsedAt(null);
        $this->assertNull($this->solution->getLastUsedAt());
    }

    public function testSuccessRateReturnsNullWithoutUsage(): void
    {
        $this->solution->setUsageCount(0);

        $this->assertNull($this->solution->getSuccessRate());
    }

    public function testSuccessRateReturnsRoundedPercentage(): void
    {
        $this->solution->setUsageCount(3);
        $this->solution->setFeedbackPositive(2);

        $this->assertSame(66.67, $this->solution->getSuccessRate());
    }

    public function testIncrementUsageCountUpdatesCountAndLastUsedAt(): void
    {
        $result = $this->solution->incrementUsageCount();

        $this->assertSame($this->solution, $result);
        $this->assertSame(1, $this->solution->getUsageCount());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->solution->getLastUsedAt());
    }

    public function testIncrementUsageCountCanBeAppliedMultipleTimes(): void
    {
        $this->solution->setUsageCount(2);
        $this->solution->setFeedbackPositive(1);
        $this->solution->setFeedbackNegative(1);

        $this->solution->incrementUsageCount();
        $this->solution->incrementUsageCount();

        $this->assertSame(4, $this->solution->getUsageCount());
        $this->assertSame(25.0, $this->solution->getSuccessRate());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->solution->getLastUsedAt());
    }

    public function testLifecycleCallbacksSetDates(): void
    {
        $this->solution->setCreatedAtValue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->solution->getCreatedAt());

        $this->solution->setUpdatedAtValue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->solution->getUpdatedAt());
    }
}
