<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409112907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        // The database already contains the legacy schema targeted by this migration.
        // Skipping it keeps the project aligned with the current database state and
        // lets the remaining forum migrations run normally.
        if (in_array('solution_traitement_vote', $tables, true)) {
            return;
        }

        if (!in_array('solution_traitement_vote', $tables, true)) {
            $this->addSql('CREATE TABLE solution_traitement_vote (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(8) NOT NULL, created_at DATETIME NOT NULL, solution_traitement_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_59EA35418400BB71 (solution_traitement_id), INDEX IDX_59EA3541FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }

        if (!in_array('messenger_messages', $tables, true)) {
            $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        }

        if (!in_array('solution_traitement_vote', $tables, true)) {
            $this->addSql('ALTER TABLE solution_traitement_vote ADD CONSTRAINT FK_59EA35418400BB71 FOREIGN KEY (solution_traitement_id) REFERENCES solution_traitement (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE solution_traitement_vote ADD CONSTRAINT FK_59EA3541FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        }
        $this->addSql('ALTER TABLE achats_fournisseurs DROP FOREIGN KEY `achats_fournisseurs_ibfk_1`');
        $this->addSql('ALTER TABLE achats_fournisseurs DROP FOREIGN KEY `achats_fournisseurs_ibfk_2`');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `fk_avis_demande`');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `fk_avis_technicien`');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `fk_avis_utilisateur`');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY `fk_demande_technicien`');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY `fk_demande_utilisateur`');
        $this->addSql('ALTER TABLE profile DROP FOREIGN KEY `fk_profile_utilisateur`');
        $this->addSql('ALTER TABLE technicien DROP FOREIGN KEY `fk_technicien_utilisateur`');
        $this->addSql('DROP TABLE achats_fournisseurs');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE demande');
        $this->addSql('DROP TABLE personne');
        $this->addSql('DROP TABLE profile');
        $this->addSql('DROP TABLE technicien');
        $this->addSql('ALTER TABLE accompagnants DROP FOREIGN KEY `fk_accompagnant_participation`');
        $this->addSql('ALTER TABLE accompagnants DROP FOREIGN KEY `fk_accompagnant_participation`');
        $this->addSql('ALTER TABLE accompagnants CHANGE id_participation id_participation INT DEFAULT NULL, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE accompagnants ADD CONSTRAINT FK_36C5F8A1157D332A FOREIGN KEY (id_participation) REFERENCES participations (id_participation)');
        $this->addSql('DROP INDEX fk_accompagnant_participation ON accompagnants');
        $this->addSql('CREATE INDEX IDX_36C5F8A1157D332A ON accompagnants (id_participation)');
        $this->addSql('ALTER TABLE accompagnants ADD CONSTRAINT `fk_accompagnant_participation` FOREIGN KEY (id_participation) REFERENCES participations (id_participation) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX nom ON categorie_forum');
        $this->addSql('ALTER TABLE categories CHANGE type_produit type_produit VARCHAR(20) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY `fk_commandes_utilisateur`');
        $this->addSql('DROP INDEX numero_commande ON commandes');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY `fk_commandes_utilisateur`');
        $this->addSql('ALTER TABLE commandes CHANGE statut_paiement statut_paiement VARCHAR(20) NOT NULL, CHANGE statut_livraison statut_livraison VARCHAR(30) NOT NULL, CHANGE adresse_livraison adresse_livraison LONGTEXT NOT NULL, CHANGE date_commande date_commande DATETIME NOT NULL, CHANGE notes notes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT FK_35D4282C50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id)');
        $this->addSql('DROP INDEX idx_commandes_utilisateur ON commandes');
        $this->addSql('CREATE INDEX IDX_35D4282C50EAE44 ON commandes (id_utilisateur)');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT `fk_commandes_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_67F068BC4B89032C ON commentaire (post_id)');
        $this->addSql('CREATE INDEX IDX_67F068BCFB88E14F ON commentaire (utilisateur_id)');
        $this->addSql('ALTER TABLE details_commandes DROP FOREIGN KEY `fk_details_commandes_commande`');
        $this->addSql('ALTER TABLE details_commandes DROP FOREIGN KEY `fk_details_commandes_equipement`');
        $this->addSql('ALTER TABLE details_commandes DROP FOREIGN KEY `fk_details_commandes_commande`');
        $this->addSql('ALTER TABLE details_commandes DROP FOREIGN KEY `fk_details_commandes_equipement`');
        $this->addSql('ALTER TABLE details_commandes ADD CONSTRAINT FK_4FD424F782EA2E54 FOREIGN KEY (commande_id) REFERENCES commandes (id)');
        $this->addSql('ALTER TABLE details_commandes ADD CONSTRAINT FK_4FD424F7806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipements (id)');
        $this->addSql('DROP INDEX idx_details_commande ON details_commandes');
        $this->addSql('CREATE INDEX IDX_4FD424F782EA2E54 ON details_commandes (commande_id)');
        $this->addSql('DROP INDEX idx_details_equipement ON details_commandes');
        $this->addSql('CREATE INDEX IDX_4FD424F7806F0F5C ON details_commandes (equipement_id)');
        $this->addSql('ALTER TABLE details_commandes ADD CONSTRAINT `fk_details_commandes_commande` FOREIGN KEY (commande_id) REFERENCES commandes (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE details_commandes ADD CONSTRAINT `fk_details_commandes_equipement` FOREIGN KEY (equipement_id) REFERENCES equipements (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY `fk_equipements_categorie`');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY `fk_equipements_fournisseur`');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY `fk_equipements_categorie`');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY `fk_equipements_fournisseur`');
        $this->addSql('ALTER TABLE equipements CHANGE description description LONGTEXT DEFAULT NULL, CHANGE quantite_stock quantite_stock INT NOT NULL, CHANGE seuil_alerte seuil_alerte INT NOT NULL, CHANGE disponible disponible TINYINT NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT FK_3F02D86BBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT FK_3F02D86B670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs (id)');
        $this->addSql('DROP INDEX idx_equipements_categorie ON equipements');
        $this->addSql('CREATE INDEX IDX_3F02D86BBCF5E72D ON equipements (categorie_id)');
        $this->addSql('DROP INDEX idx_equipements_fournisseur ON equipements');
        $this->addSql('CREATE INDEX IDX_3F02D86B670C757F ON equipements (fournisseur_id)');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT `fk_equipements_categorie` FOREIGN KEY (categorie_id) REFERENCES categories (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT `fk_equipements_fournisseur` FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE evenements CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE type_evenement type_evenement VARCHAR(50) NOT NULL, CHANGE date_fin date_fin DATETIME NOT NULL, CHANGE horaire_debut horaire_debut TIME NOT NULL, CHANGE horaire_fin horaire_fin TIME NOT NULL, CHANGE lieu lieu VARCHAR(255) DEFAULT NULL, CHANGE adresse adresse VARCHAR(255) DEFAULT NULL, CHANGE capacite_max capacite_max INT NOT NULL, CHANGE places_disponibles places_disponibles INT NOT NULL, CHANGE organisateur organisateur VARCHAR(255) DEFAULT NULL, CHANGE contact_email contact_email VARCHAR(255) DEFAULT NULL, CHANGE contact_tel contact_tel VARCHAR(50) DEFAULT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE date_modification date_modification DATETIME NOT NULL');
        $this->addSql('ALTER TABLE fournisseurs CHANGE adresse adresse LONGTEXT DEFAULT NULL, CHANGE actif actif TINYINT NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY `fk_locations_terrain`');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY `fk_locations_utilisateur`');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY `fk_locations_vehicule`');
        $this->addSql('DROP INDEX numero_location ON locations');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY `fk_locations_terrain`');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY `fk_locations_utilisateur`');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY `fk_locations_vehicule`');
        $this->addSql('ALTER TABLE locations CHANGE type_location type_location VARCHAR(20) NOT NULL, CHANGE caution caution NUMERIC(10, 2) NOT NULL, CHANGE statut statut VARCHAR(20) NOT NULL, CHANGE date_reservation date_reservation DATETIME NOT NULL, CHANGE notes notes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT FK_17E64ABA50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id)');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT FK_17E64ABA4A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicules (id)');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT FK_17E64ABA8A2D8B41 FOREIGN KEY (terrain_id) REFERENCES terrains (id)');
        $this->addSql('DROP INDEX idx_locations_utilisateur ON locations');
        $this->addSql('CREATE INDEX IDX_17E64ABA50EAE44 ON locations (id_utilisateur)');
        $this->addSql('DROP INDEX idx_locations_vehicule ON locations');
        $this->addSql('CREATE INDEX IDX_17E64ABA4A4A3511 ON locations (vehicule_id)');
        $this->addSql('DROP INDEX idx_locations_terrain ON locations');
        $this->addSql('CREATE INDEX IDX_17E64ABA8A2D8B41 ON locations (terrain_id)');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT `fk_locations_terrain` FOREIGN KEY (terrain_id) REFERENCES terrains (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT `fk_locations_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT `fk_locations_vehicule` FOREIGN KEY (vehicule_id) REFERENCES vehicules (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY `participations_ibfk_1`');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY `participations_ibfk_2`');
        $this->addSql('DROP INDEX unique_participation ON participations');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY `participations_ibfk_2`');
        $this->addSql('ALTER TABLE participations CHANGE id_evenement id_evenement INT DEFAULT NULL, CHANGE id_utilisateur id_utilisateur INT DEFAULT NULL, CHANGE statut statut VARCHAR(50) NOT NULL, CHANGE date_inscription date_inscription DATETIME NOT NULL, CHANGE nombre_accompagnants nombre_accompagnants INT NOT NULL, CHANGE commentaire commentaire LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT FK_FDC6C6E88B13D439 FOREIGN KEY (id_evenement) REFERENCES evenements (id_evenement)');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT FK_FDC6C6E850EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id)');
        $this->addSql('DROP INDEX id_utilisateur ON participations');
        $this->addSql('CREATE INDEX IDX_FDC6C6E850EAE44 ON participations (id_utilisateur)');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT `participations_ibfk_2` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY `fk_post_utilisateur`');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY `fk_post_utilisateur`');
        $this->addSql('ALTER TABLE post CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE categorie categorie VARCHAR(255) DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_post_utilisateur ON post');
        $this->addSql('CREATE INDEX IDX_5A8A6C8DFB88E14F ON post (utilisateur_id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT `fk_post_utilisateur` FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY `fk_solution_maladie`');
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY `fk_solution_maladie`');
        $this->addSql('ALTER TABLE solution_traitement CHANGE solution solution LONGTEXT NOT NULL, CHANGE etapes etapes LONGTEXT DEFAULT NULL, CHANGE produits_recommandes produits_recommandes LONGTEXT DEFAULT NULL, CHANGE conseils_prevention conseils_prevention LONGTEXT DEFAULT NULL, CHANGE usage_count usage_count INT NOT NULL, CHANGE feedback_positive feedback_positive INT NOT NULL, CHANGE feedback_negative feedback_negative INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT FK_375480F0B4B1C397 FOREIGN KEY (maladie_id) REFERENCES maladie (id_maladie)');
        $this->addSql('DROP INDEX idx_maladie_id ON solution_traitement');
        $this->addSql('CREATE INDEX IDX_375480F0B4B1C397 ON solution_traitement (maladie_id)');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT `fk_solution_maladie` FOREIGN KEY (maladie_id) REFERENCES maladie (id_maladie) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sponsors CHANGE montant_contribution montant_contribution NUMERIC(10, 2) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE terrains DROP FOREIGN KEY `fk_terrains_categorie`');
        $this->addSql('ALTER TABLE terrains DROP FOREIGN KEY `fk_terrains_categorie`');
        $this->addSql('ALTER TABLE terrains CHANGE description description LONGTEXT DEFAULT NULL, CHANGE adresse adresse LONGTEXT DEFAULT NULL, CHANGE caution caution NUMERIC(10, 2) NOT NULL, CHANGE disponible disponible TINYINT NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE terrains ADD CONSTRAINT FK_A7A03A42BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories (id)');
        $this->addSql('DROP INDEX idx_terrains_categorie ON terrains');
        $this->addSql('CREATE INDEX IDX_A7A03A42BCF5E72D ON terrains (categorie_id)');
        $this->addSql('ALTER TABLE terrains ADD CONSTRAINT `fk_terrains_categorie` FOREIGN KEY (categorie_id) REFERENCES categories (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE utilisateurs CHANGE adresse adresse LONGTEXT DEFAULT NULL, CHANGE type_user type_user VARCHAR(20) NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('DROP INDEX email ON utilisateurs');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_497B315EE7927C74 ON utilisateurs (email)');
        $this->addSql('ALTER TABLE vehicules DROP FOREIGN KEY `fk_vehicules_categorie`');
        $this->addSql('DROP INDEX immatriculation ON vehicules');
        $this->addSql('ALTER TABLE vehicules DROP FOREIGN KEY `fk_vehicules_categorie`');
        $this->addSql('ALTER TABLE vehicules CHANGE description description LONGTEXT DEFAULT NULL, CHANGE caution caution NUMERIC(10, 2) NOT NULL, CHANGE disponible disponible TINYINT NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE vehicules ADD CONSTRAINT FK_78218C2DBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories (id)');
        $this->addSql('DROP INDEX idx_vehicules_categorie ON vehicules');
        $this->addSql('CREATE INDEX IDX_78218C2DBCF5E72D ON vehicules (categorie_id)');
        $this->addSql('ALTER TABLE vehicules ADD CONSTRAINT `fk_vehicules_categorie` FOREIGN KEY (categorie_id) REFERENCES categories (id) ON UPDATE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE achats_fournisseurs (id INT AUTO_INCREMENT NOT NULL, fournisseur_id INT NOT NULL, equipement_id INT NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, date_achat DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX equipement_id (equipement_id), INDEX idx_fournisseur (fournisseur_id), INDEX idx_date (date_achat), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE avis (id_avis INT AUTO_INCREMENT NOT NULL, id_utilisateur INT NOT NULL, note INT DEFAULT NULL, commentaire TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_avis DATE DEFAULT NULL, id_tech INT DEFAULT NULL, id_demande INT DEFAULT NULL, INDEX idx_avis_utilisateur (id_utilisateur), INDEX idx_avis_technicien (id_tech), INDEX fk_avis_technicien (id_tech), INDEX idx_avis_demande (id_demande), INDEX fk_avis_demande (id_demande), PRIMARY KEY (id_avis)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE demande (id_demande INT AUTO_INCREMENT NOT NULL, id_utilisateur INT DEFAULT NULL, type_probleme VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_demande DATE DEFAULT NULL, statut VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, id_tech INT DEFAULT NULL, adresse_client VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_demande_technicien (id_tech), INDEX fk_demande_utilisateur (id_utilisateur), PRIMARY KEY (id_demande)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE personne (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prenom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE profile (id_profile INT AUTO_INCREMENT NOT NULL, id_utilisateur INT NOT NULL, photo_profil VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, bio TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_naissance DATE DEFAULT NULL, genre ENUM(\'homme\', \'femme\', \'autre\') CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, pays VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, ville VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, derniere_mise_a_jour DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX fk_profile_utilisateur (id_utilisateur), PRIMARY KEY (id_profile)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE technicien (id_tech INT AUTO_INCREMENT NOT NULL, id_utilisateur INT DEFAULT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prenom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, specialite VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, telephone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, disponibilite TINYINT DEFAULT 1, localisation TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, age INT DEFAULT NULL, date_naissance DATE DEFAULT NULL, cin VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, partage_position TINYINT DEFAULT 0, derniere_maj_position DATETIME DEFAULT NULL, password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, partage_auto TINYINT DEFAULT 0, UNIQUE INDEX email (email), INDEX idx_technicien_utilisateur (id_utilisateur), UNIQUE INDEX id_utilisateur (id_utilisateur), UNIQUE INDEX email_2 (email), UNIQUE INDEX cin (cin), PRIMARY KEY (id_tech)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE achats_fournisseurs ADD CONSTRAINT `achats_fournisseurs_ibfk_1` FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs (id)');
        $this->addSql('ALTER TABLE achats_fournisseurs ADD CONSTRAINT `achats_fournisseurs_ibfk_2` FOREIGN KEY (equipement_id) REFERENCES equipements (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `fk_avis_demande` FOREIGN KEY (id_demande) REFERENCES demande (id_demande) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `fk_avis_technicien` FOREIGN KEY (id_tech) REFERENCES technicien (id_tech) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `fk_avis_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT `fk_demande_technicien` FOREIGN KEY (id_tech) REFERENCES technicien (id_tech) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT `fk_demande_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profile ADD CONSTRAINT `fk_profile_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE technicien ADD CONSTRAINT `fk_technicien_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solution_traitement_vote DROP FOREIGN KEY FK_59EA35418400BB71');
        $this->addSql('ALTER TABLE solution_traitement_vote DROP FOREIGN KEY FK_59EA3541FB88E14F');
        $this->addSql('DROP TABLE solution_traitement_vote');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE accompagnants DROP FOREIGN KEY FK_36C5F8A1157D332A');
        $this->addSql('ALTER TABLE accompagnants DROP FOREIGN KEY FK_36C5F8A1157D332A');
        $this->addSql('ALTER TABLE accompagnants CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE prenom prenom VARCHAR(100) NOT NULL, CHANGE id_participation id_participation INT NOT NULL');
        $this->addSql('ALTER TABLE accompagnants ADD CONSTRAINT `fk_accompagnant_participation` FOREIGN KEY (id_participation) REFERENCES participations (id_participation) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_36c5f8a1157d332a ON accompagnants');
        $this->addSql('CREATE INDEX fk_accompagnant_participation ON accompagnants (id_participation)');
        $this->addSql('ALTER TABLE accompagnants ADD CONSTRAINT FK_36C5F8A1157D332A FOREIGN KEY (id_participation) REFERENCES participations (id_participation)');
        $this->addSql('ALTER TABLE categories CHANGE type_produit type_produit ENUM(\'equipement\', \'vehicule\', \'terrain\') NOT NULL, CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX nom ON categorie_forum (nom)');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY FK_35D4282C50EAE44');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY FK_35D4282C50EAE44');
        $this->addSql('ALTER TABLE commandes CHANGE statut_paiement statut_paiement ENUM(\'en_attente\', \'paye\', \'echoue\') DEFAULT \'en_attente\', CHANGE statut_livraison statut_livraison ENUM(\'en_attente\', \'en_preparation\', \'expedie\', \'livre\', \'annule\') DEFAULT \'en_attente\', CHANGE adresse_livraison adresse_livraison TEXT NOT NULL, CHANGE date_commande date_commande DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE notes notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT `fk_commandes_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX numero_commande ON commandes (numero_commande)');
        $this->addSql('DROP INDEX idx_35d4282c50eae44 ON commandes');
        $this->addSql('CREATE INDEX idx_commandes_utilisateur ON commandes (id_utilisateur)');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT FK_35D4282C50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id)');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC4B89032C');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCFB88E14F');
        $this->addSql('DROP INDEX IDX_67F068BC4B89032C ON commentaire');
        $this->addSql('DROP INDEX IDX_67F068BCFB88E14F ON commentaire');
        $this->addSql('ALTER TABLE commentaire CHANGE contenu contenu TEXT NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE details_commandes DROP FOREIGN KEY FK_4FD424F782EA2E54');
        $this->addSql('ALTER TABLE details_commandes DROP FOREIGN KEY FK_4FD424F7806F0F5C');
        $this->addSql('ALTER TABLE details_commandes DROP FOREIGN KEY FK_4FD424F782EA2E54');
        $this->addSql('ALTER TABLE details_commandes DROP FOREIGN KEY FK_4FD424F7806F0F5C');
        $this->addSql('ALTER TABLE details_commandes ADD CONSTRAINT `fk_details_commandes_commande` FOREIGN KEY (commande_id) REFERENCES commandes (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE details_commandes ADD CONSTRAINT `fk_details_commandes_equipement` FOREIGN KEY (equipement_id) REFERENCES equipements (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_4fd424f782ea2e54 ON details_commandes');
        $this->addSql('CREATE INDEX idx_details_commande ON details_commandes (commande_id)');
        $this->addSql('DROP INDEX idx_4fd424f7806f0f5c ON details_commandes');
        $this->addSql('CREATE INDEX idx_details_equipement ON details_commandes (equipement_id)');
        $this->addSql('ALTER TABLE details_commandes ADD CONSTRAINT FK_4FD424F782EA2E54 FOREIGN KEY (commande_id) REFERENCES commandes (id)');
        $this->addSql('ALTER TABLE details_commandes ADD CONSTRAINT FK_4FD424F7806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipements (id)');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY FK_3F02D86BBCF5E72D');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY FK_3F02D86B670C757F');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY FK_3F02D86BBCF5E72D');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY FK_3F02D86B670C757F');
        $this->addSql('ALTER TABLE equipements CHANGE description description TEXT DEFAULT NULL, CHANGE quantite_stock quantite_stock INT DEFAULT 0, CHANGE seuil_alerte seuil_alerte INT DEFAULT 5, CHANGE disponible disponible TINYINT DEFAULT 1, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT `fk_equipements_categorie` FOREIGN KEY (categorie_id) REFERENCES categories (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT `fk_equipements_fournisseur` FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs (id) ON UPDATE CASCADE');
        $this->addSql('DROP INDEX idx_3f02d86bbcf5e72d ON equipements');
        $this->addSql('CREATE INDEX idx_equipements_categorie ON equipements (categorie_id)');
        $this->addSql('DROP INDEX idx_3f02d86b670c757f ON equipements');
        $this->addSql('CREATE INDEX idx_equipements_fournisseur ON equipements (fournisseur_id)');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT FK_3F02D86BBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT FK_3F02D86B670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs (id)');
        $this->addSql('ALTER TABLE evenements CHANGE titre titre VARCHAR(200) NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE type_evenement type_evenement ENUM(\'exposition\', \'atelier\', \'conference\', \'salon\', \'formation\', \'autre\') DEFAULT NULL, CHANGE date_fin date_fin DATETIME DEFAULT NULL, CHANGE horaire_debut horaire_debut TIME DEFAULT NULL, CHANGE horaire_fin horaire_fin TIME DEFAULT NULL, CHANGE lieu lieu VARCHAR(200) DEFAULT NULL, CHANGE adresse adresse TEXT DEFAULT NULL, CHANGE capacite_max capacite_max INT DEFAULT NULL, CHANGE places_disponibles places_disponibles INT DEFAULT NULL, CHANGE organisateur organisateur VARCHAR(100) DEFAULT NULL, CHANGE contact_email contact_email VARCHAR(100) DEFAULT NULL, CHANGE contact_tel contact_tel VARCHAR(20) DEFAULT NULL, CHANGE statut statut ENUM(\'actif\', \'annule\', \'termine\', \'complet\') DEFAULT \'actif\', CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE date_modification date_modification DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE fournisseurs CHANGE adresse adresse TEXT DEFAULT NULL, CHANGE actif actif TINYINT DEFAULT 1, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY FK_17E64ABA50EAE44');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY FK_17E64ABA4A4A3511');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY FK_17E64ABA8A2D8B41');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY FK_17E64ABA50EAE44');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY FK_17E64ABA4A4A3511');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY FK_17E64ABA8A2D8B41');
        $this->addSql('ALTER TABLE locations CHANGE type_location type_location ENUM(\'vehicule\', \'terrain\') NOT NULL, CHANGE caution caution NUMERIC(10, 2) DEFAULT \'0.00\', CHANGE statut statut ENUM(\'en_attente\', \'confirmee\', \'en_cours\', \'terminee\', \'annulee\') DEFAULT \'en_attente\', CHANGE date_reservation date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE notes notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT `fk_locations_terrain` FOREIGN KEY (terrain_id) REFERENCES terrains (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT `fk_locations_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT `fk_locations_vehicule` FOREIGN KEY (vehicule_id) REFERENCES vehicules (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX numero_location ON locations (numero_location)');
        $this->addSql('DROP INDEX idx_17e64aba8a2d8b41 ON locations');
        $this->addSql('CREATE INDEX idx_locations_terrain ON locations (terrain_id)');
        $this->addSql('DROP INDEX idx_17e64aba50eae44 ON locations');
        $this->addSql('CREATE INDEX idx_locations_utilisateur ON locations (id_utilisateur)');
        $this->addSql('DROP INDEX idx_17e64aba4a4a3511 ON locations');
        $this->addSql('CREATE INDEX idx_locations_vehicule ON locations (vehicule_id)');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT FK_17E64ABA50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id)');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT FK_17E64ABA4A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicules (id)');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT FK_17E64ABA8A2D8B41 FOREIGN KEY (terrain_id) REFERENCES terrains (id)');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY FK_FDC6C6E88B13D439');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY FK_FDC6C6E850EAE44');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY FK_FDC6C6E850EAE44');
        $this->addSql('ALTER TABLE participations CHANGE statut statut ENUM(\'en_attente\', \'confirme\', \'annule\') DEFAULT \'confirme\', CHANGE date_inscription date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE nombre_accompagnants nombre_accompagnants INT DEFAULT 0, CHANGE commentaire commentaire TEXT DEFAULT NULL, CHANGE id_evenement id_evenement INT NOT NULL, CHANGE id_utilisateur id_utilisateur INT NOT NULL');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT `participations_ibfk_1` FOREIGN KEY (id_evenement) REFERENCES evenements (id_evenement) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT `participations_ibfk_2` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX unique_participation ON participations (id_evenement, id_utilisateur)');
        $this->addSql('DROP INDEX idx_fdc6c6e850eae44 ON participations');
        $this->addSql('CREATE INDEX id_utilisateur ON participations (id_utilisateur)');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT FK_FDC6C6E850EAE44 FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id)');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DFB88E14F');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DFB88E14F');
        $this->addSql('ALTER TABLE post CHANGE contenu contenu TEXT NOT NULL, CHANGE categorie categorie VARCHAR(100) DEFAULT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE statut statut VARCHAR(50) DEFAULT \'actif\'');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT `fk_post_utilisateur` FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_5a8a6c8dfb88e14f ON post');
        $this->addSql('CREATE INDEX fk_post_utilisateur ON post (utilisateur_id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY FK_375480F0B4B1C397');
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY FK_375480F0B4B1C397');
        $this->addSql('ALTER TABLE solution_traitement CHANGE solution solution TEXT NOT NULL, CHANGE etapes etapes TEXT DEFAULT NULL, CHANGE produits_recommandes produits_recommandes TEXT DEFAULT NULL, CHANGE conseils_prevention conseils_prevention TEXT DEFAULT NULL, CHANGE usage_count usage_count INT DEFAULT 0 NOT NULL, CHANGE feedback_positive feedback_positive INT DEFAULT 0 NOT NULL, CHANGE feedback_negative feedback_negative INT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT `fk_solution_maladie` FOREIGN KEY (maladie_id) REFERENCES maladie (id_maladie) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_375480f0b4b1c397 ON solution_traitement');
        $this->addSql('CREATE INDEX idx_maladie_id ON solution_traitement (maladie_id)');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT FK_375480F0B4B1C397 FOREIGN KEY (maladie_id) REFERENCES maladie (id_maladie)');
        $this->addSql('ALTER TABLE sponsors CHANGE montant_contribution montant_contribution NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE terrains DROP FOREIGN KEY FK_A7A03A42BCF5E72D');
        $this->addSql('ALTER TABLE terrains DROP FOREIGN KEY FK_A7A03A42BCF5E72D');
        $this->addSql('ALTER TABLE terrains CHANGE description description TEXT DEFAULT NULL, CHANGE adresse adresse TEXT DEFAULT NULL, CHANGE caution caution NUMERIC(10, 2) DEFAULT \'0.00\', CHANGE disponible disponible TINYINT DEFAULT 1, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE terrains ADD CONSTRAINT `fk_terrains_categorie` FOREIGN KEY (categorie_id) REFERENCES categories (id) ON UPDATE CASCADE');
        $this->addSql('DROP INDEX idx_a7a03a42bcf5e72d ON terrains');
        $this->addSql('CREATE INDEX idx_terrains_categorie ON terrains (categorie_id)');
        $this->addSql('ALTER TABLE terrains ADD CONSTRAINT FK_A7A03A42BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories (id)');
        $this->addSql('ALTER TABLE utilisateurs CHANGE adresse adresse TEXT DEFAULT NULL, CHANGE type_user type_user ENUM(\'client\', \'admin\', \'technicien\') DEFAULT \'client\' NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX uniq_497b315ee7927c74 ON utilisateurs');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateurs (email)');
        $this->addSql('ALTER TABLE vehicules DROP FOREIGN KEY FK_78218C2DBCF5E72D');
        $this->addSql('ALTER TABLE vehicules DROP FOREIGN KEY FK_78218C2DBCF5E72D');
        $this->addSql('ALTER TABLE vehicules CHANGE description description TEXT DEFAULT NULL, CHANGE caution caution NUMERIC(10, 2) DEFAULT \'0.00\', CHANGE disponible disponible TINYINT DEFAULT 1, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE vehicules ADD CONSTRAINT `fk_vehicules_categorie` FOREIGN KEY (categorie_id) REFERENCES categories (id) ON UPDATE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX immatriculation ON vehicules (immatriculation)');
        $this->addSql('DROP INDEX idx_78218c2dbcf5e72d ON vehicules');
        $this->addSql('CREATE INDEX idx_vehicules_categorie ON vehicules (categorie_id)');
        $this->addSql('ALTER TABLE vehicules ADD CONSTRAINT FK_78218C2DBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories (id)');
    }
}
