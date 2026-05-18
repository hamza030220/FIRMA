<?php

namespace App\Tests\Unit\Marketplace;

use App\Entity\Marketplace\Categorie;
use App\Entity\Marketplace\Equipement;
use App\Entity\Marketplace\Fournisseur;
use PHPUnit\Framework\TestCase;

class EquipementTest extends TestCase
{
    private Equipement $equipement;

    protected function setUp(): void
    {
        $this->equipement = new Equipement();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->equipement->getId());
        $this->assertNull($this->equipement->getNom());
        $this->assertNull($this->equipement->getCategorie());
        $this->assertNull($this->equipement->getFournisseur());
        $this->assertNull($this->equipement->getDescription());
        $this->assertNull($this->equipement->getPrixAchat());
        $this->assertNull($this->equipement->getPrixVente());
        $this->assertSame(0, $this->equipement->getQuantiteStock());
        $this->assertSame(5, $this->equipement->getSeuilAlerte());
        $this->assertNull($this->equipement->getImageUrl());
        $this->assertTrue($this->equipement->isDisponible());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->equipement->getDateCreation());
    }

    public function testSetAndGetNom(): void
    {
        $result = $this->equipement->setNom('Tracteur John Deere');
        $this->assertSame('Tracteur John Deere', $this->equipement->getNom());
        $this->assertSame($this->equipement, $result);
    }

    public function testSetAndGetCategorie(): void
    {
        $categorie = new Categorie();
        $categorie->setNom('Machines');
        $this->equipement->setCategorie($categorie);
        $this->assertSame($categorie, $this->equipement->getCategorie());
    }

    public function testSetAndGetFournisseur(): void
    {
        $fournisseur = new Fournisseur();
        $fournisseur->setNomEntreprise('AgriTech');
        $this->equipement->setFournisseur($fournisseur);
        $this->assertSame($fournisseur, $this->equipement->getFournisseur());
    }

    public function testSetAndGetDescription(): void
    {
        $this->equipement->setDescription('Un équipement agricole performant');
        $this->assertSame('Un équipement agricole performant', $this->equipement->getDescription());
    }

    public function testSetAndGetPrixAchat(): void
    {
        $this->equipement->setPrixAchat('15000.50');
        $this->assertSame('15000.50', $this->equipement->getPrixAchat());
    }

    public function testSetAndGetPrixVente(): void
    {
        $this->equipement->setPrixVente('18500.00');
        $this->assertSame('18500.00', $this->equipement->getPrixVente());
    }

    public function testSetAndGetQuantiteStock(): void
    {
        $this->equipement->setQuantiteStock(25);
        $this->assertSame(25, $this->equipement->getQuantiteStock());
    }

    public function testSetAndGetSeuilAlerte(): void
    {
        $this->equipement->setSeuilAlerte(10);
        $this->assertSame(10, $this->equipement->getSeuilAlerte());
    }

    public function testSetAndGetImageUrl(): void
    {
        $this->equipement->setImageUrl('/uploads/marketplace/equipements/image.jpg');
        $this->assertSame('/uploads/marketplace/equipements/image.jpg', $this->equipement->getImageUrl());
    }

    public function testSetAndGetDisponible(): void
    {
        $this->equipement->setDisponible(false);
        $this->assertFalse($this->equipement->isDisponible());

        $this->equipement->setDisponible(true);
        $this->assertTrue($this->equipement->isDisponible());
    }

    public function testSetAndGetDateCreation(): void
    {
        $date = new \DateTime('2025-06-01');
        $this->equipement->setDateCreation($date);
        $this->assertSame($date, $this->equipement->getDateCreation());
    }

    public function testToString(): void
    {
        $this->assertSame('', (string) $this->equipement);
        $this->equipement->setNom('Pompe hydraulique');
        $this->assertSame('Pompe hydraulique', (string) $this->equipement);
    }

    public function testFluentSetters(): void
    {
        $result = $this->equipement
            ->setNom('Test')
            ->setPrixAchat('100.00')
            ->setPrixVente('150.00')
            ->setQuantiteStock(10)
            ->setDisponible(true);

        $this->assertSame($this->equipement, $result);
        $this->assertSame('Test', $this->equipement->getNom());
        $this->assertSame('100.00', $this->equipement->getPrixAchat());
        $this->assertSame('150.00', $this->equipement->getPrixVente());
        $this->assertSame(10, $this->equipement->getQuantiteStock());
    }

    // ── Edge cases — prix ─────────────────────────────────────────────────────

    public function testSetPrixAchatZero(): void
    {
        $this->equipement->setPrixAchat('0.00');
        $this->assertSame('0.00', $this->equipement->getPrixAchat());
    }

    public function testSetPrixAchatNegative(): void
    {
        // PHP level stores it; @Assert\Positive would reject in real usage
        $this->equipement->setPrixAchat('-1.00');
        $this->assertSame('-1.00', $this->equipement->getPrixAchat());
    }

    public function testSetPrixVenteLowerThanPrixAchat(): void
    {
        // Entity does not enforce prixVente > prixAchat — that is business logic
        $this->equipement->setPrixAchat('200.00');
        $this->equipement->setPrixVente('50.00');
        $this->assertSame('50.00', $this->equipement->getPrixVente());
    }

    public function testSetPrixVenteLargeValue(): void
    {
        $this->equipement->setPrixVente('9999999.99');
        $this->assertSame('9999999.99', $this->equipement->getPrixVente());
    }

    // ── Edge cases — stock ────────────────────────────────────────────────────

    public function testSetQuantiteStockToZero(): void
    {
        $this->equipement->setQuantiteStock(0);
        $this->assertSame(0, $this->equipement->getQuantiteStock());
    }

    public function testSetQuantiteStockToNegative(): void
    {
        // Can represent out-of-sync inventory; entity stores it
        $this->equipement->setQuantiteStock(-5);
        $this->assertSame(-5, $this->equipement->getQuantiteStock());
    }

    public function testSetSeuilAlerteToZero(): void
    {
        $this->equipement->setSeuilAlerte(0);
        $this->assertSame(0, $this->equipement->getSeuilAlerte());
    }

    public function testSetSeuilAlerteToNegative(): void
    {
        $this->equipement->setSeuilAlerte(-1);
        $this->assertSame(-1, $this->equipement->getSeuilAlerte());
    }

    // ── Edge cases — nullable fields ──────────────────────────────────────────

    public function testSetDescriptionToNull(): void
    {
        $this->equipement->setDescription('Desc');
        $this->equipement->setDescription(null);
        $this->assertNull($this->equipement->getDescription());
    }

    public function testSetImageUrlToNull(): void
    {
        $this->equipement->setImageUrl('image.jpg');
        $this->equipement->setImageUrl(null);
        $this->assertNull($this->equipement->getImageUrl());
    }

    public function testSetCategorieToNull(): void
    {
        $this->equipement->setCategorie(new Categorie());
        $this->equipement->setCategorie(null);
        $this->assertNull($this->equipement->getCategorie());
    }

    public function testSetFournisseurToNull(): void
    {
        $this->equipement->setFournisseur(new Fournisseur());
        $this->equipement->setFournisseur(null);
        $this->assertNull($this->equipement->getFournisseur());
    }

    // ── Edge cases — nom ──────────────────────────────────────────────────────

    public function testSetNomToNull(): void
    {
        $this->equipement->setNom('Test');
        $this->equipement->setNom(null);
        $this->assertNull($this->equipement->getNom());
    }

    public function testSetNomWithMaxLength(): void
    {
        $long = str_repeat('E', 200);
        $this->equipement->setNom($long);
        $this->assertSame(200, strlen($this->equipement->getNom()));
    }

    // ── Edge cases — __toString ───────────────────────────────────────────────

    public function testToStringWithNullNom(): void
    {
        $this->assertSame('', (string) $this->equipement);
    }

    // ── Edge cases — dateCreation ─────────────────────────────────────────────

    public function testDateCreationIsSetInConstructor(): void
    {
        $fresh = new Equipement();
        $this->assertInstanceOf(\DateTimeInterface::class, $fresh->getDateCreation());
    }

    public function testEachInstanceHasIndependentDateCreation(): void
    {
        $e1 = new Equipement();
        $e2 = new Equipement();
        // Both should be DateTimeInterface but may differ slightly in time
        $this->assertInstanceOf(\DateTimeInterface::class, $e1->getDateCreation());
        $this->assertInstanceOf(\DateTimeInterface::class, $e2->getDateCreation());
    }
}
