<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403172335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE accompagnants DROP FOREIGN KEY `fk_accompagnant_participation`');
        $this->addSql('ALTER TABLE achats_fournisseurs DROP FOREIGN KEY `achats_fournisseurs_ibfk_1`');
        $this->addSql('ALTER TABLE achats_fournisseurs DROP FOREIGN KEY `achats_fournisseurs_ibfk_2`');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `fk_avis_demande`');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `fk_avis_technicien`');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY `fk_avis_utilisateur`');
        $this->addSql('ALTER TABLE commandes DROP FOREIGN KEY `fk_commandes_utilisateur`');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY `commentaire_ibfk_1`');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY `fk_commentaire_utilisateur`');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY `fk_demande_technicien`');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY `fk_demande_utilisateur`');
        $this->addSql('ALTER TABLE details_commandes DROP FOREIGN KEY `fk_details_commandes_commande`');
        $this->addSql('ALTER TABLE details_commandes DROP FOREIGN KEY `fk_details_commandes_equipement`');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY `fk_equipements_categorie`');
        $this->addSql('ALTER TABLE equipements DROP FOREIGN KEY `fk_equipements_fournisseur`');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY `fk_locations_terrain`');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY `fk_locations_utilisateur`');
        $this->addSql('ALTER TABLE locations DROP FOREIGN KEY `fk_locations_vehicule`');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY `participations_ibfk_1`');
        $this->addSql('ALTER TABLE participations DROP FOREIGN KEY `participations_ibfk_2`');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY `fk_post_utilisateur`');
        $this->addSql('ALTER TABLE profile DROP FOREIGN KEY `fk_profile_utilisateur`');
        $this->addSql('ALTER TABLE technicien DROP FOREIGN KEY `fk_technicien_utilisateur`');
        $this->addSql('ALTER TABLE terrains DROP FOREIGN KEY `fk_terrains_categorie`');
        $this->addSql('ALTER TABLE vehicules DROP FOREIGN KEY `fk_vehicules_categorie`');
        $this->addSql('DROP TABLE accompagnants');
        $this->addSql('DROP TABLE achats_fournisseurs');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE categorie_forum');
        $this->addSql('DROP TABLE commandes');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE demande');
        $this->addSql('DROP TABLE details_commandes');
        $this->addSql('DROP TABLE equipements');
        $this->addSql('DROP TABLE evenements');
        $this->addSql('DROP TABLE fournisseurs');
        $this->addSql('DROP TABLE locations');
        $this->addSql('DROP TABLE participations');
        $this->addSql('DROP TABLE personne');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE profile');
        $this->addSql('DROP TABLE technicien');
        $this->addSql('DROP TABLE terrains');
        $this->addSql('DROP TABLE vehicules');
        $this->addSql('ALTER TABLE maladie CHANGE description description LONGTEXT DEFAULT NULL, CHANGE symptomes symptomes LONGTEXT DEFAULT NULL, CHANGE niveau_gravite niveau_gravite VARCHAR(20) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY `fk_solution_maladie`');
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY `fk_solution_maladie`');
        $this->addSql('ALTER TABLE solution_traitement CHANGE solution solution LONGTEXT NOT NULL, CHANGE etapes etapes LONGTEXT DEFAULT NULL, CHANGE produits_recommandes produits_recommandes LONGTEXT DEFAULT NULL, CHANGE conseils_prevention conseils_prevention LONGTEXT DEFAULT NULL, CHANGE usage_count usage_count INT NOT NULL, CHANGE feedback_positive feedback_positive INT NOT NULL, CHANGE feedback_negative feedback_negative INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT FK_375480F0B4B1C397 FOREIGN KEY (maladie_id) REFERENCES maladie (id_maladie)');
        $this->addSql('DROP INDEX idx_maladie_id ON solution_traitement');
        $this->addSql('CREATE INDEX IDX_375480F0B4B1C397 ON solution_traitement (maladie_id)');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT `fk_solution_maladie` FOREIGN KEY (maladie_id) REFERENCES maladie (id_maladie) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateurs CHANGE adresse adresse LONGTEXT DEFAULT NULL, CHANGE type_user type_user VARCHAR(20) NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('DROP INDEX email ON utilisateurs');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_497B315EE7927C74 ON utilisateurs (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE accompagnants (id_accompagnant INT AUTO_INCREMENT NOT NULL, id_participation INT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prenom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_accompagnant_participation (id_participation), PRIMARY KEY (id_accompagnant)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE achats_fournisseurs (id INT AUTO_INCREMENT NOT NULL, fournisseur_id INT NOT NULL, equipement_id INT NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, date_achat DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX equipement_id (equipement_id), INDEX idx_fournisseur (fournisseur_id), INDEX idx_date (date_achat), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE avis (id_avis INT AUTO_INCREMENT NOT NULL, id_utilisateur INT NOT NULL, note INT DEFAULT NULL, commentaire TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_avis DATE DEFAULT NULL, id_tech INT DEFAULT NULL, id_demande INT DEFAULT NULL, INDEX idx_avis_utilisateur (id_utilisateur), INDEX idx_avis_technicien (id_tech), INDEX fk_avis_technicien (id_tech), INDEX idx_avis_demande (id_demande), INDEX fk_avis_demande (id_demande), PRIMARY KEY (id_avis)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, type_produit ENUM(\'equipement\', \'vehicule\', \'terrain\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE categorie_forum (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, UNIQUE INDEX nom (nom), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commandes (id INT AUTO_INCREMENT NOT NULL, id_utilisateur INT NOT NULL, numero_commande VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, montant_total NUMERIC(10, 2) NOT NULL, statut_paiement ENUM(\'en_attente\', \'paye\', \'echoue\') CHARACTER SET utf8mb4 DEFAULT \'en_attente\' COLLATE `utf8mb4_general_ci`, statut_livraison ENUM(\'en_attente\', \'en_preparation\', \'expedie\', \'livre\', \'annule\') CHARACTER SET utf8mb4 DEFAULT \'en_attente\' COLLATE `utf8mb4_general_ci`, adresse_livraison TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, ville_livraison VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_commande DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, date_livraison DATE DEFAULT NULL, notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, UNIQUE INDEX numero_commande (numero_commande), INDEX idx_commandes_utilisateur (id_utilisateur), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, utilisateur_id INT NOT NULL, contenu TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX post_id (post_id), INDEX fk_commentaire_utilisateur (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE demande (id_demande INT AUTO_INCREMENT NOT NULL, id_utilisateur INT DEFAULT NULL, type_probleme VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_demande DATE DEFAULT NULL, statut VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, id_tech INT DEFAULT NULL, adresse_client VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_demande_technicien (id_tech), INDEX fk_demande_utilisateur (id_utilisateur), PRIMARY KEY (id_demande)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE details_commandes (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, equipement_id INT NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, sous_total NUMERIC(10, 2) NOT NULL, INDEX idx_details_commande (commande_id), INDEX idx_details_equipement (equipement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE equipements (id INT AUTO_INCREMENT NOT NULL, categorie_id INT NOT NULL, fournisseur_id INT NOT NULL, nom VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, prix_achat NUMERIC(10, 2) NOT NULL, prix_vente NUMERIC(10, 2) NOT NULL, quantite_stock INT DEFAULT 0, seuil_alerte INT DEFAULT 5, image_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, disponible TINYINT DEFAULT 1, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_equipements_categorie (categorie_id), INDEX idx_equipements_fournisseur (fournisseur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE evenements (id_evenement INT AUTO_INCREMENT NOT NULL, titre VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, type_evenement ENUM(\'exposition\', \'atelier\', \'conference\', \'salon\', \'formation\', \'autre\') CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, horaire_debut TIME DEFAULT NULL, horaire_fin TIME DEFAULT NULL, lieu VARCHAR(200) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, adresse TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, capacite_max INT DEFAULT NULL, places_disponibles INT DEFAULT NULL, organisateur VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, contact_email VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, contact_tel VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, statut ENUM(\'actif\', \'annule\', \'termine\', \'complet\') CHARACTER SET utf8mb4 DEFAULT \'actif\' COLLATE `utf8mb4_general_ci`, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, date_modification DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id_evenement)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE fournisseurs (id INT AUTO_INCREMENT NOT NULL, nom_entreprise VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, contact_nom VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, telephone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, adresse TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, ville VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, actif TINYINT DEFAULT 1, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE locations (id INT AUTO_INCREMENT NOT NULL, id_utilisateur INT NOT NULL, type_location ENUM(\'vehicule\', \'terrain\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, vehicule_id INT DEFAULT NULL, terrain_id INT DEFAULT NULL, numero_location VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_debut DATE NOT NULL, date_fin DATE NOT NULL, duree_jours INT DEFAULT NULL, prix_total NUMERIC(10, 2) NOT NULL, caution NUMERIC(10, 2) DEFAULT \'0.00\', statut ENUM(\'en_attente\', \'confirmee\', \'en_cours\', \'terminee\', \'annulee\') CHARACTER SET utf8mb4 DEFAULT \'en_attente\' COLLATE `utf8mb4_general_ci`, date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, notes TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX idx_locations_utilisateur (id_utilisateur), INDEX idx_locations_vehicule (vehicule_id), INDEX idx_locations_terrain (terrain_id), UNIQUE INDEX numero_location (numero_location), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE participations (id_participation INT AUTO_INCREMENT NOT NULL, id_evenement INT NOT NULL, id_utilisateur INT NOT NULL, statut ENUM(\'en_attente\', \'confirme\', \'annule\') CHARACTER SET utf8mb4 DEFAULT \'confirme\' COLLATE `utf8mb4_general_ci`, date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, date_annulation DATETIME DEFAULT NULL, nombre_accompagnants INT DEFAULT 0, commentaire TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, code_participation VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX id_utilisateur (id_utilisateur), UNIQUE INDEX unique_participation (id_evenement, id_utilisateur), INDEX IDX_FDC6C6E88B13D439 (id_evenement), PRIMARY KEY (id_participation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE personne (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prenom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, contenu TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, categorie VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, statut VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'actif\' COLLATE `utf8mb4_general_ci`, INDEX fk_post_utilisateur (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE profile (id_profile INT AUTO_INCREMENT NOT NULL, id_utilisateur INT NOT NULL, photo_profil VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, bio TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_naissance DATE DEFAULT NULL, genre ENUM(\'homme\', \'femme\', \'autre\') CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, pays VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, ville VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, derniere_mise_a_jour DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX fk_profile_utilisateur (id_utilisateur), PRIMARY KEY (id_profile)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE technicien (id_tech INT AUTO_INCREMENT NOT NULL, id_utilisateur INT DEFAULT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prenom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, specialite VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, telephone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, disponibilite TINYINT DEFAULT 1, localisation TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, age INT DEFAULT NULL, date_naissance DATE DEFAULT NULL, cin VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, partage_position TINYINT DEFAULT 0, derniere_maj_position DATETIME DEFAULT NULL, password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, partage_auto TINYINT DEFAULT 0, UNIQUE INDEX email (email), INDEX idx_technicien_utilisateur (id_utilisateur), UNIQUE INDEX id_utilisateur (id_utilisateur), UNIQUE INDEX email_2 (email), UNIQUE INDEX cin (cin), PRIMARY KEY (id_tech)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE terrains (id INT AUTO_INCREMENT NOT NULL, categorie_id INT NOT NULL, titre VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, superficie_hectares NUMERIC(10, 2) NOT NULL, ville VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, adresse TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, prix_mois NUMERIC(10, 2) DEFAULT NULL, prix_annee NUMERIC(10, 2) NOT NULL, caution NUMERIC(10, 2) DEFAULT \'0.00\', image_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, disponible TINYINT DEFAULT 1, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_terrains_categorie (categorie_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE vehicules (id INT AUTO_INCREMENT NOT NULL, categorie_id INT NOT NULL, nom VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, marque VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, modele VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, immatriculation VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, prix_jour NUMERIC(10, 2) NOT NULL, prix_semaine NUMERIC(10, 2) DEFAULT NULL, prix_mois NUMERIC(10, 2) DEFAULT NULL, caution NUMERIC(10, 2) DEFAULT \'0.00\', image_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, disponible TINYINT DEFAULT 1, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX immatriculation (immatriculation), INDEX idx_vehicules_categorie (categorie_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE accompagnants ADD CONSTRAINT `fk_accompagnant_participation` FOREIGN KEY (id_participation) REFERENCES participations (id_participation) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE achats_fournisseurs ADD CONSTRAINT `achats_fournisseurs_ibfk_1` FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs (id)');
        $this->addSql('ALTER TABLE achats_fournisseurs ADD CONSTRAINT `achats_fournisseurs_ibfk_2` FOREIGN KEY (equipement_id) REFERENCES equipements (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `fk_avis_demande` FOREIGN KEY (id_demande) REFERENCES demande (id_demande) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `fk_avis_technicien` FOREIGN KEY (id_tech) REFERENCES technicien (id_tech) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT `fk_avis_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commandes ADD CONSTRAINT `fk_commandes_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT `commentaire_ibfk_1` FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT `fk_commentaire_utilisateur` FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT `fk_demande_technicien` FOREIGN KEY (id_tech) REFERENCES technicien (id_tech) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT `fk_demande_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE details_commandes ADD CONSTRAINT `fk_details_commandes_commande` FOREIGN KEY (commande_id) REFERENCES commandes (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE details_commandes ADD CONSTRAINT `fk_details_commandes_equipement` FOREIGN KEY (equipement_id) REFERENCES equipements (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT `fk_equipements_categorie` FOREIGN KEY (categorie_id) REFERENCES categories (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE equipements ADD CONSTRAINT `fk_equipements_fournisseur` FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT `fk_locations_terrain` FOREIGN KEY (terrain_id) REFERENCES terrains (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT `fk_locations_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE locations ADD CONSTRAINT `fk_locations_vehicule` FOREIGN KEY (vehicule_id) REFERENCES vehicules (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT `participations_ibfk_1` FOREIGN KEY (id_evenement) REFERENCES evenements (id_evenement) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participations ADD CONSTRAINT `participations_ibfk_2` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT `fk_post_utilisateur` FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profile ADD CONSTRAINT `fk_profile_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE technicien ADD CONSTRAINT `fk_technicien_utilisateur` FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE terrains ADD CONSTRAINT `fk_terrains_categorie` FOREIGN KEY (categorie_id) REFERENCES categories (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE vehicules ADD CONSTRAINT `fk_vehicules_categorie` FOREIGN KEY (categorie_id) REFERENCES categories (id) ON UPDATE CASCADE');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE maladie CHANGE description description TEXT DEFAULT NULL, CHANGE symptomes symptomes TEXT DEFAULT NULL, CHANGE niveau_gravite niveau_gravite ENUM(\'faible\', \'moyen\', \'eleve\', \'critique\') DEFAULT \'moyen\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY FK_375480F0B4B1C397');
        $this->addSql('ALTER TABLE solution_traitement DROP FOREIGN KEY FK_375480F0B4B1C397');
        $this->addSql('ALTER TABLE solution_traitement CHANGE solution solution TEXT NOT NULL, CHANGE etapes etapes TEXT DEFAULT NULL, CHANGE produits_recommandes produits_recommandes TEXT DEFAULT NULL, CHANGE conseils_prevention conseils_prevention TEXT DEFAULT NULL, CHANGE usage_count usage_count INT DEFAULT 0 NOT NULL, CHANGE feedback_positive feedback_positive INT DEFAULT 0 NOT NULL, CHANGE feedback_negative feedback_negative INT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT `fk_solution_maladie` FOREIGN KEY (maladie_id) REFERENCES maladie (id_maladie) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_375480f0b4b1c397 ON solution_traitement');
        $this->addSql('CREATE INDEX idx_maladie_id ON solution_traitement (maladie_id)');
        $this->addSql('ALTER TABLE solution_traitement ADD CONSTRAINT FK_375480F0B4B1C397 FOREIGN KEY (maladie_id) REFERENCES maladie (id_maladie)');
        $this->addSql('ALTER TABLE utilisateurs CHANGE adresse adresse TEXT DEFAULT NULL, CHANGE type_user type_user ENUM(\'client\', \'admin\', \'technicien\') DEFAULT \'client\' NOT NULL, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX uniq_497b315ee7927c74 ON utilisateurs');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateurs (email)');
    }
}
