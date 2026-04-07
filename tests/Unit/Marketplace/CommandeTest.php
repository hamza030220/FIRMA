<?php

namespace App\Tests\Unit\Marketplace;

use App\Entity\Marketplace\Commande;
use App\Entity\Marketplace\DetailCommande;
use App\Entity\Marketplace\Equipement;
use App\Entity\User\Utilisateur;
use PHPUnit\Framework\TestCase;

class CommandeTest extends TestCase
{
    private Commande $commande;

    protected function setUp(): void
    {
        $this->commande = new Commande();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->commande->getId());
        $this->assertNull($this->commande->getUtilisateur());
        $this->assertNull($this->commande->getNumeroCommande());
        $this->assertNull($this->commande->getMontantTotal());
        $this->assertSame('en_attente', $this->commande->getStatutPaiement());
        $this->assertSame('en_attente', $this->commande->getStatutLivraison());
        $this->assertNull($this->commande->getAdresseLivraison());
        $this->assertNull($this->commande->getVilleLivraison());
        $this->assertInstanceOf(\DateTimeInterface::class, $this->commande->getDateCommande());
        $this->assertNull($this->commande->getDateLivraison());
        $this->assertNull($this->commande->getNotes());
        $this->assertCount(0, $this->commande->getDetails());
    }

    public function testSetAndGetUtilisateur(): void
    {
        $user = new Utilisateur();
        $result = $this->commande->setUtilisateur($user);
        $this->assertSame($user, $this->commande->getUtilisateur());
        $this->assertSame($this->commande, $result);
    }

    public function testSetAndGetNumeroCommande(): void
    {
        $this->commande->setNumeroCommande('CMD-2025-001');
        $this->assertSame('CMD-2025-001', $this->commande->getNumeroCommande());
    }

    public function testSetAndGetMontantTotal(): void
    {
        $this->commande->setMontantTotal('1250.75');
        $this->assertSame('1250.75', $this->commande->getMontantTotal());
    }

    public function testSetAndGetStatutPaiement(): void
    {
        $statuts = ['en_attente', 'payee', 'echouee', 'remboursee'];
        foreach ($statuts as $statut) {
            $this->commande->setStatutPaiement($statut);
            $this->assertSame($statut, $this->commande->getStatutPaiement());
        }
    }

    public function testSetAndGetStatutLivraison(): void
    {
        $statuts = ['en_preparation', 'expediee', 'livree', 'annulee'];
        foreach ($statuts as $statut) {
            $this->commande->setStatutLivraison($statut);
            $this->assertSame($statut, $this->commande->getStatutLivraison());
        }
    }

    public function testSetAndGetAdresseLivraison(): void
    {
        $this->commande->setAdresseLivraison('123 Rue de la Liberté');
        $this->assertSame('123 Rue de la Liberté', $this->commande->getAdresseLivraison());
    }

    public function testSetAndGetVilleLivraison(): void
    {
        $this->commande->setVilleLivraison('Tunis');
        $this->assertSame('Tunis', $this->commande->getVilleLivraison());
    }

    public function testSetAndGetDateCommande(): void
    {
        $date = new \DateTime('2025-12-25 14:30:00');
        $this->commande->setDateCommande($date);
        $this->assertSame($date, $this->commande->getDateCommande());
    }

    public function testSetAndGetDateLivraison(): void
    {
        $date = new \DateTime('2025-12-30');
        $this->commande->setDateLivraison($date);
        $this->assertSame($date, $this->commande->getDateLivraison());

        $this->commande->setDateLivraison(null);
        $this->assertNull($this->commande->getDateLivraison());
    }

    public function testSetAndGetNotes(): void
    {
        $this->commande->setNotes('Livraison urgente');
        $this->assertSame('Livraison urgente', $this->commande->getNotes());

        $this->commande->setNotes(null);
        $this->assertNull($this->commande->getNotes());
    }

    public function testDetailsCollection(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->commande->getDetails());
        $this->assertCount(0, $this->commande->getDetails());
    }
}
