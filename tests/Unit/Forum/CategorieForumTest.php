<?php

namespace App\Tests\Unit\Forum;

use App\Entity\Forum\CategorieForum;
use PHPUnit\Framework\TestCase;

class CategorieForumTest extends TestCase
{
    private CategorieForum $categorie;

    protected function setUp(): void
    {
        $this->categorie = new CategorieForum();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->categorie->getId());
        $this->assertNull($this->categorie->getNom());
    }

    public function testSetAndGetNom(): void
    {
        $result = $this->categorie->setNom('Conseils agricoles');

        $this->assertSame('Conseils agricoles', $this->categorie->getNom());
        $this->assertSame($this->categorie, $result);
    }

    public function testCategorieCanBeRenamed(): void
    {
        $this->categorie->setNom('Conseils agricoles');
        $this->assertSame('Conseils agricoles', $this->categorie->getNom());

        $this->categorie->setNom('Gestion des sols');
        $this->assertSame('Gestion des sols', $this->categorie->getNom());

        $this->categorie->setNom('Forum general');
        $this->assertSame('Forum general', $this->categorie->getNom());
    }
}
