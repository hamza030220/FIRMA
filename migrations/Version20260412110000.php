<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260412110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes to marketplace tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_equip_categorie ON equipements (categorie_id)');
        $this->addSql('CREATE INDEX idx_equip_fournisseur ON equipements (fournisseur_id)');
        $this->addSql('CREATE INDEX idx_equip_disponible ON equipements (disponible)');
        $this->addSql('CREATE INDEX idx_equip_date_creation ON equipements (date_creation)');

        $this->addSql('CREATE INDEX idx_vehic_categorie ON vehicules (categorie_id)');
        $this->addSql('CREATE INDEX idx_vehic_disponible ON vehicules (disponible)');
        $this->addSql('CREATE INDEX idx_vehic_date_creation ON vehicules (date_creation)');

        $this->addSql('CREATE INDEX idx_terrain_categorie ON terrains (categorie_id)');
        $this->addSql('CREATE INDEX idx_terrain_disponible ON terrains (disponible)');
        $this->addSql('CREATE INDEX idx_terrain_date_creation ON terrains (date_creation)');

        $this->addSql('CREATE INDEX idx_cmd_utilisateur ON commandes (id_utilisateur)');
        $this->addSql('CREATE INDEX idx_cmd_date ON commandes (date_commande)');
        $this->addSql('CREATE INDEX idx_cmd_statut_paiement ON commandes (statut_paiement)');
        $this->addSql('CREATE INDEX idx_cmd_statut_livraison ON commandes (statut_livraison)');

        $this->addSql('CREATE INDEX idx_loc_utilisateur ON locations (id_utilisateur)');
        $this->addSql('CREATE INDEX idx_loc_date_reservation ON locations (date_reservation)');
        $this->addSql('CREATE INDEX idx_loc_statut ON locations (statut)');
        $this->addSql('CREATE INDEX idx_loc_type ON locations (type_location)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_equip_categorie ON equipements');
        $this->addSql('DROP INDEX idx_equip_fournisseur ON equipements');
        $this->addSql('DROP INDEX idx_equip_disponible ON equipements');
        $this->addSql('DROP INDEX idx_equip_date_creation ON equipements');

        $this->addSql('DROP INDEX idx_vehic_categorie ON vehicules');
        $this->addSql('DROP INDEX idx_vehic_disponible ON vehicules');
        $this->addSql('DROP INDEX idx_vehic_date_creation ON vehicules');

        $this->addSql('DROP INDEX idx_terrain_categorie ON terrains');
        $this->addSql('DROP INDEX idx_terrain_disponible ON terrains');
        $this->addSql('DROP INDEX idx_terrain_date_creation ON terrains');

        $this->addSql('DROP INDEX idx_cmd_utilisateur ON commandes');
        $this->addSql('DROP INDEX idx_cmd_date ON commandes');
        $this->addSql('DROP INDEX idx_cmd_statut_paiement ON commandes');
        $this->addSql('DROP INDEX idx_cmd_statut_livraison ON commandes');

        $this->addSql('DROP INDEX idx_loc_utilisateur ON locations');
        $this->addSql('DROP INDEX idx_loc_date_reservation ON locations');
        $this->addSql('DROP INDEX idx_loc_statut ON locations');
        $this->addSql('DROP INDEX idx_loc_type ON locations');
    }
}
