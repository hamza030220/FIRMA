<?php

namespace App\Tests\Unit\Maladie;

use App\Entity\Maladie\MaladieCase;
use App\Entity\Maladie\MaladieCasePhoto;
use App\Entity\Maladie\MaladieCaseUpdate;
use App\Entity\Maladie\SolutionTraitement;
use PHPUnit\Framework\TestCase;

class MaladieCaseUpdateTest extends TestCase
{
    private MaladieCaseUpdate $update;

    protected function setUp(): void
    {
        $this->update = new MaladieCaseUpdate();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->update->getId());
        $this->assertNull($this->update->getCase());
        $this->assertNull($this->update->getSolutionTraitement());
        $this->assertNull($this->update->getResultat());
        $this->assertNull($this->update->getCommentaire());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->update->getCreatedAt());
        $this->assertCount(0, $this->update->getPhotos());
    }

    public function testSetAndGetCase(): void
    {
        $case = new MaladieCase();
        $result = $this->update->setCase($case);

        $this->assertSame($case, $this->update->getCase());
        $this->assertSame($this->update, $result);

        $this->update->setCase(null);
        $this->assertNull($this->update->getCase());
    }

    public function testSetAndGetSolutionTraitement(): void
    {
        $solution = new SolutionTraitement();
        $result = $this->update->setSolutionTraitement($solution);

        $this->assertSame($solution, $this->update->getSolutionTraitement());
        $this->assertSame($this->update, $result);

        $this->update->setSolutionTraitement(null);
        $this->assertNull($this->update->getSolutionTraitement());
    }

    public function testSetAndGetResultat(): void
    {
        foreach (['amelioration', 'stable', 'echec'] as $resultat) {
            $this->update->setResultat($resultat);
            $this->assertSame($resultat, $this->update->getResultat());
        }
    }

    public function testSetAndGetCommentaire(): void
    {
        $this->update->setCommentaire('Les symptomes diminuent.');
        $this->assertSame('Les symptomes diminuent.', $this->update->getCommentaire());

        $this->update->setCommentaire(null);
        $this->assertNull($this->update->getCommentaire());
    }

    public function testSetCreatedAtValue(): void
    {
        $this->update->initializeTimestamp();

        $this->assertInstanceOf(\DateTimeImmutable::class, $this->update->getCreatedAt());
    }

    public function testPhotosCollection(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->update->getPhotos());

        $this->update->getPhotos()->add(new MaladieCasePhoto());
        $this->assertCount(1, $this->update->getPhotos());
    }

    public function testCaseUpdateCanBeConfiguredWithTreatmentAndPhotos(): void
    {
        $case = new MaladieCase();
        $solution = new SolutionTraitement();
        $photo = new MaladieCasePhoto();

        $this->update
            ->setCase($case)
            ->setSolutionTraitement($solution)
            ->setResultat('amelioration')
            ->setCommentaire('Les symptomes diminuent progressivement.');

        $this->update->getPhotos()->add($photo);
        $this->update->initializeTimestamp();

        $this->assertSame($case, $this->update->getCase());
        $this->assertSame($solution, $this->update->getSolutionTraitement());
        $this->assertSame('amelioration', $this->update->getResultat());
        $this->assertSame('Les symptomes diminuent progressivement.', $this->update->getCommentaire());
        $this->assertCount(1, $this->update->getPhotos());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->update->getCreatedAt());
    }
}
