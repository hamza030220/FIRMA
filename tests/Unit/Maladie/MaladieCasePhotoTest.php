<?php

namespace App\Tests\Unit\Maladie;

use App\Entity\Maladie\MaladieCasePhoto;
use App\Entity\Maladie\MaladieCaseUpdate;
use PHPUnit\Framework\TestCase;

class MaladieCasePhotoTest extends TestCase
{
    private MaladieCasePhoto $photo;

    protected function setUp(): void
    {
        $this->photo = new MaladieCasePhoto();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->photo->getId());
        $this->assertNull($this->photo->getCaseUpdate());
        $this->assertNull($this->photo->getFilename());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->photo->getCreatedAt());
    }

    public function testSetAndGetCaseUpdate(): void
    {
        $update = new MaladieCaseUpdate();
        $result = $this->photo->setCaseUpdate($update);

        $this->assertSame($update, $this->photo->getCaseUpdate());
        $this->assertSame($this->photo, $result);

        $this->photo->setCaseUpdate(null);
        $this->assertNull($this->photo->getCaseUpdate());
    }

    public function testSetAndGetFilename(): void
    {
        $result = $this->photo->setFilename('feuille-malade.jpg');

        $this->assertSame('feuille-malade.jpg', $this->photo->getFilename());
        $this->assertSame($this->photo, $result);
    }

    public function testSetCreatedAtValue(): void
    {
        $this->photo->initializeTimestamp();

        $this->assertInstanceOf(\DateTimeImmutable::class, $this->photo->getCreatedAt());
    }

    public function testPhotoCanBeConfiguredEndToEnd(): void
    {
        $update = new MaladieCaseUpdate();
        $this->photo
            ->setCaseUpdate($update)
            ->setFilename('feuille-malade.jpg');

        $this->photo->initializeTimestamp();

        $this->assertSame($update, $this->photo->getCaseUpdate());
        $this->assertSame('feuille-malade.jpg', $this->photo->getFilename());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->photo->getCreatedAt());
    }
}
