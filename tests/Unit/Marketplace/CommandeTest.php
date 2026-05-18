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

    // ── Edge cases — montantTotal ─────────────────────────────────────────────

    public function testSetMontantTotalZero(): void
    {
        $this->commande->setMontantTotal('0.00');
        $this->assertSame('0.00', $this->commande->getMontantTotal());
    }

    public function testSetMontantTotalNegative(): void
    {
        // PHP level stores it; validation handles the real constraint
        $this->commande->setMontantTotal('-100.00');
        $this->assertSame('-100.00', $this->commande->getMontantTotal());
    }

    public function testSetMontantTotalLargeValue(): void
    {
        $this->commande->setMontantTotal('99999999.99');
        $this->assertSame('99999999.99', $this->commande->getMontantTotal());
    }

    // ── Edge cases — statuts ──────────────────────────────────────────────────

    public function testSetStatutPaiementUnknownValue(): void
    {
        // PHP level stores it; business logic/validators handle allowed values
        $this->commande->setStatutPaiement('inconnu');
        $this->assertSame('inconnu', $this->commande->getStatutPaiement());
    }

    public function testSetStatutLivraisonUnknownValue(): void
    {
        $this->commande->setStatutLivraison('inconnu');
        $this->assertSame('inconnu', $this->commande->getStatutLivraison());
    }

    // ── Edge cases — adresse & ville ─────────────────────────────────────────

    public function testSetVilleLivraisonToNull(): void
    {
        $this->commande->setVilleLivraison('Tunis');
        $this->commande->setVilleLivraison(null);
        $this->assertNull($this->commande->getVilleLivraison());
    }

    public function testSetAdresseLivraisonWithLongText(): void
    {
        $long = str_repeat('A', 500);
        $this->commande->setAdresseLivraison($long);
        $this->assertSame(500, strlen($this->commande->getAdresseLivraison()));
    }

    // ── Edge cases — dates ────────────────────────────────────────────────────

    public function testDateLivraisonCanBeBeforeDateCommande(): void
    {
        // Entity does not enforce order — that is a form/validator concern
        $commande = new \DateTime('2026-06-01');
        $livraison = new \DateTime('2026-01-01');
        $this->commande->setDateCommande($commande);
        $this->commande->setDateLivraison($livraison);
        $this->assertSame($livraison, $this->commande->getDateLivraison());
    }

    public function testDateCommandeIsSetInConstructor(): void
    {
        $fresh = new \App\Entity\Marketplace\Commande();
        $this->assertInstanceOf(\DateTimeInterface::class, $fresh->getDateCommande());
    }

    // ── Edge cases — utilisateur ──────────────────────────────────────────────

    public function testSetUtilisateurToNull(): void
    {
        $user = new Utilisateur();
        $this->commande->setUtilisateur($user);
        $this->commande->setUtilisateur(null);
        $this->assertNull($this->commande->getUtilisateur());
    }

    // ── Edge cases — numeroCommande ───────────────────────────────────────────

    public function testSetNumeroCommandeToNull(): void
    {
        $this->commande->setNumeroCommande('CMD-001');
        $this->commande->setNumeroCommande(null);
        $this->assertNull($this->commande->getNumeroCommande());
    }

    public function testSetNumeroCommandeFluentInterface(): void
    {
        $result = $this->commande->setNumeroCommande('CMD-XYZ');
        $this->assertSame($this->commande, $result);
    }

    // ── Edge cases — notes ────────────────────────────────────────────────────

    public function testSetNotesWithLongText(): void
    {
        $long = str_repeat('note ', 200);
        $this->commande->setNotes($long);
        $this->assertSame(strlen($long), strlen($this->commande->getNotes()));
    }
}
